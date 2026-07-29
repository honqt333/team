<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ServiceBay;
use App\Support\Permissions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceBayController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can(Permissions::APPOINTMENTS_MANAGE), 403);

        $bays = ServiceBay::query()
            ->where('center_id', $user->current_center_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'type' => $b->type,
                'is_active' => (bool) $b->is_active,
                'notes' => $b->notes,
                'sort_order' => (int) $b->sort_order,
                'has_active_appointments' => $b->is_active
                    ? $b->hasActiveAppointments()
                    : false,
            ]);

        return Inertia::render('Settings/Centers/ServiceBays', [
            'bays' => $bays,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', ServiceBay::class);

        $data = $this->validated($request, null);
        $user = $request->user();

        $bay = ServiceBay::create(array_merge($data, [
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
        ]));

        return $request->expectsJson()
            ? response()->json(['success' => __('messages.bay_created'), 'bay' => $bay])
            : redirect()->back()->with('success', __('messages.bay_created'));
    }

    public function update(Request $request, ServiceBay $serviceBay): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $serviceBay);

        $data = $this->validated($request, $serviceBay);
        $serviceBay->update($data);

        return $request->expectsJson()
            ? response()->json(['success' => __('messages.bay_updated'), 'bay' => $serviceBay])
            : redirect()->back()->with('success', __('messages.bay_updated'));
    }

    public function destroy(Request $request, ServiceBay $serviceBay): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $serviceBay);

        // Don't allow deleting a bay with future appointments —
        // the operator should deactivate it instead. Otherwise
        // the next time someone opens the calendar, the FK
        // relationship vanishes and the schedule is broken.
        if ($serviceBay->hasActiveAppointments()) {
            $message = __('messages.bay_has_active_appointments');

            return $request->expectsJson()
                ? response()->json(['error' => $message, 'code' => 'bay_has_active_appointments'], 422)
                : redirect()->back()->with('error', $message);
        }

        $serviceBay->delete();

        return $request->expectsJson()
            ? response()->json(['success' => __('messages.bay_deleted')])
            : redirect()->back()->with('success', __('messages.bay_deleted'));
    }

    private function validated(Request $request, ?ServiceBay $bay): array
    {
        $centerId = $request->user()->current_center_id;
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:32',
            'type' => 'nullable|in:'.implode(',', array_keys(ServiceBay::TYPES)),
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'sometimes|integer|min:0',
        ]);
    }
}
