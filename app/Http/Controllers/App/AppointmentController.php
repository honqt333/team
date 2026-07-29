<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AppointmentConversionService;
use App\Services\AppointmentSchedulingService;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AppointmentSchedulingService $scheduler,
        private readonly AppointmentConversionService $converter,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can(Permissions::APPOINTMENTS_MANAGE), 403);

        $centerId = $user->current_center_id;
        $tenantId = $user->tenant_id;

        $query = Appointment::query()
            ->where('center_id', $centerId)
            ->with([
                'customer:id,name,phone',
                'vehicle:id,plate_number,customer_id',
                'serviceBay:id,name',
                'assignedUser:id,name',
                'services:id,name,base_price',
            ]);

        if ($date = $request->query('date')) {
            $query->whereDate('scheduled_at', $date);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($bayId = $request->query('service_bay_id')) {
            $query->where('service_bay_id', $bayId);
        }

        if ($techId = $request->query('assigned_user_id')) {
            $query->where('assigned_user_id', $techId);
        }

        $appointments = $query
            ->orderBy('scheduled_at')
            ->limit(500)
            ->get()
            ->map(fn (Appointment $a) => $this->serialize($a));

        // Supporting lists for the create / filter UI.
        $bays = ServiceBay::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $techs = User::query()
            ->whereHas('centers', fn ($q) => $q->where('centers.id', $centerId))
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        $services = Service::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'base_price', 'duration_value', 'duration_unit']);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'bays' => $bays,
            'techs' => $techs,
            'services' => $services,
            'filters' => [
                'date' => $request->query('date'),
                'status' => $request->query('status'),
                'service_bay_id' => $request->query('service_bay_id'),
                'assigned_user_id' => $request->query('assigned_user_id'),
            ],
            'statuses' => Appointment::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_bay_id' => 'nullable|exists:service_bays,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'required|date',
            'estimated_end_at' => 'required|date|after:scheduled_at',
            'service_ids' => 'array',
            'service_ids.*' => 'exists:services,id',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:'.implode(',', Appointment::STATUSES),
        ]);

        $user = $request->user();
        $customer = Customer::findOrFail($data['customer_id']);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $bay = isset($data['service_bay_id']) ? ServiceBay::find($data['service_bay_id']) : null;
        $tech = isset($data['assigned_user_id']) ? User::find($data['assigned_user_id']) : null;

        try {
            $appointment = $this->scheduler->create(
                Carbon::parse($data['scheduled_at']),
                Carbon::parse($data['estimated_end_at']),
                $customer,
                $vehicle,
                $bay,
                $tech,
                $data['service_ids'] ?? [],
                ['status' => $data['status'] ?? Appointment::STATUS_SCHEDULED, 'notes' => $data['notes'] ?? null]
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            'appointment_created',
            $appointment->fresh()->load(['customer', 'vehicle', 'serviceBay', 'assignedUser', 'services'])
        );
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            // `present` lets the field be null (a real "clear" signal)
            // without Laravel treating the missing key and the null
            // value as the same thing. We need that distinction
            // here so the operator can explicitly un-assign a bay
            // or technician.
            'service_bay_id' => 'sometimes|nullable|exists:service_bays,id',
            'assigned_user_id' => 'sometimes|nullable|exists:users,id',
            'scheduled_at' => 'required|date',
            'estimated_end_at' => 'required|date|after:scheduled_at',
            'service_ids' => 'array',
            'service_ids.*' => 'exists:services,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Three-state resolution per resource:
        //   key missing       → keep current value
        //   key = numeric id  → swap to that resource
        //   key = null         → clear the slot entirely
        $changeBay = array_key_exists('service_bay_id', $data);
        $bay = $changeBay && $data['service_bay_id']
            ? ServiceBay::find($data['service_bay_id'])
            : null;

        $changeAssignee = array_key_exists('assigned_user_id', $data);
        $tech = $changeAssignee && $data['assigned_user_id']
            ? User::find($data['assigned_user_id'])
            : null;

        try {
            $this->scheduler->reschedule(
                $appointment,
                Carbon::parse($data['scheduled_at']),
                Carbon::parse($data['estimated_end_at']),
                changeBay: $changeBay,
                bay: $bay,
                changeAssignee: $changeAssignee,
                assignedUser: $tech,
            );

            if (array_key_exists('notes', $data)) {
                $appointment->update(['notes' => $data['notes']]);
            }

            if (array_key_exists('service_ids', $data)) {
                $this->syncServices($appointment, $data['service_ids']);
            }
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'appointment_updated', $appointment->refresh());
    }

    public function checkIn(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $appointment);

        try {
            $this->scheduler->checkIn($appointment);
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'appointment_checked_in', $appointment->refresh());
    }

    public function markNoShow(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $appointment);

        try {
            $this->scheduler->markNoShow($appointment);
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'appointment_no_show', $appointment->refresh());
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->scheduler->cancel($appointment, $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'appointment_cancelled', $appointment->refresh());
    }

    public function convert(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $appointment);

        try {
            $workOrder = $this->converter->convert($appointment, $request->user());
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'appointment_converted', [
            'work_order_id' => $workOrder->id,
            'work_order_code' => $workOrder->code,
        ]);
    }

    // ==================== Helpers ====================

    private function syncServices(Appointment $appointment, array $serviceIds): void
    {
        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->where('center_id', $appointment->center_id)
            ->get();

        $sync = [];

        foreach ($services as $service) {
            $sync[$service->id] = [
                'estimated_minutes' => $this->minutesFromService($service),
                'updated_at' => now(),
            ];
        }
        $appointment->services()->sync($sync);
    }

    private function minutesFromService(Service $service): ?int
    {
        if (! $service->duration_value) {
            return null;
        }

        return match ($service->duration_unit ?? 'minute') {
            'hour' => (int) $service->duration_value * 60,
            'day' => (int) $service->duration_value * 60 * 24,
            default => (int) $service->duration_value,
        };
    }

    private function serialize(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'status' => $a->status,
            'scheduled_at' => $a->scheduled_at?->toIso8601String(),
            'estimated_end_at' => $a->estimated_end_at?->toIso8601String(),
            'checked_in_at' => $a->checked_in_at?->toIso8601String(),
            'cancelled_at' => $a->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $a->cancellation_reason,
            'notes' => $a->notes,
            'work_order_id' => $a->work_order_id,
            'customer' => $a->customer ? [
                'id' => $a->customer->id,
                'name' => $a->customer->name,
                'phone' => $a->customer->phone,
            ] : null,
            'vehicle' => $a->vehicle ? [
                'id' => $a->vehicle->id,
                'plate_number' => $a->vehicle->plate_number,
            ] : null,
            'service_bay' => $a->serviceBay ? [
                'id' => $a->serviceBay->id,
                'name' => $a->serviceBay->name,
            ] : null,
            'assigned_user' => $a->assignedUser ? [
                'id' => $a->assignedUser->id,
                'name' => $a->assignedUser->name,
            ] : null,
            'services' => $a->services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'base_price' => (float) ($s->base_price ?? 0),
                'estimated_minutes' => $s->pivot->estimated_minutes ?? null,
            ])->all(),
        ];
    }

    private function errorResponse(Request $request, string $code, int $status): RedirectResponse|JsonResponse
    {
        $message = __("messages.{$code}");

        if ($message === "messages.{$code}") {
            $message = $code;
        }

        return $request->expectsJson()
            ? response()->json(['error' => $message, 'code' => $code], $status)
            : redirect()->back()->with('error', $message);
    }

    private function successResponse(Request $request, string $code, mixed $payload): RedirectResponse|JsonResponse
    {
        $message = __("messages.{$code}");

        if ($message === "messages.{$code}") {
            $message = $code;
        }

        return $request->expectsJson()
            ? response()->json(['success' => $message, 'payload' => $payload])
            : redirect()->back()->with('success', $message);
    }
}
