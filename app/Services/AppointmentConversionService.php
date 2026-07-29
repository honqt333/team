<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Models\Appointment;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Converts an appointment into a work order. The contract is:
 *
 *   - Only appointments in scheduled / confirmed / checked_in may
 *     be converted. The caller should have moved the appointment
 *     to checked_in before calling this if the customer is at the
 *     counter, but the service also accepts scheduled / confirmed
 *     so a same-day pre-emptive conversion is possible.
 *   - Idempotent: calling convert() on the same appointment twice
 *     returns the same work order. The status flips to
 *     `converted` only on the first successful call.
 *   - The work order is created in the same tenant + center as
 *     the appointment, with the same customer and vehicle.
 *   - The pivot services are translated to WorkOrderItems with
 *     qty=1 and unit_price=0 by default. The full quote /
 *     pricing pipeline runs separately in the work order flow
 *     — this stage is purely "open a card, attach the booked
 *     services as items so the technicians see them".
 *   - No inventory moves, no payment receipts, no QC. The work
 *     order is created in `open` status with the items ready for
 *     the technician to start.
 */
class AppointmentConversionService
{
    public function __construct(private readonly CreateWorkOrderAction $createWorkOrder) {}

    public function convert(Appointment $appointment, User $actor): WorkOrder
    {
        if ($appointment->isConverted() && $appointment->work_order_id) {
            // Idempotent fast path — the appointment was already
            // converted. Return the existing work order, no
            // double-create, no double-status-flip.
            $existing = WorkOrder::find($appointment->work_order_id);

            if ($existing) {
                return $existing;
            }
        }

        if (! $appointment->isConvertible()) {
            throw new RuntimeException('appointment_not_convertible');
        }

        return DB::transaction(function () use ($appointment, $actor) {
            // Lock the appointment row so a parallel conversion
            // request can't both pass the "is converted?" check.
            $locked = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new RuntimeException('appointment_not_found');
            }

            if ($locked->isConverted() && $locked->work_order_id) {
                $existing = WorkOrder::find($locked->work_order_id);

                if ($existing) {
                    return $existing;
                }
            }

            $services = $locked->services()->with('department')->get();

            $items = $services->map(function ($service) {
                return [
                    'service_id' => $service->id,
                    'department_id' => $service->department_id,
                    'title' => $service->name,
                    'qty' => 1,
                    'unit_price' => (float) ($service->base_price ?? 0),
                ];
            })->all();

            // CreateWorkOrderAction generates the code with locking
            // and seeds the items. We pass the appointment context
            // through notes / expected_end_date.
            $workOrder = $this->createWorkOrder->execute($actor, [
                'customer_id' => $locked->customer_id,
                'vehicle_id' => $locked->vehicle_id,
                'status' => WorkOrder::STATUS_OPEN,
                'notes' => $locked->notes,
                'entry_date' => $locked->scheduled_at?->toDateString() ?? Carbon::now()->toDateString(),
                'expected_end_date' => $locked->estimated_end_at?->toDateString(),
                'customer_complaint' => $locked->notes,
                'items' => $items,
            ]);

            $locked->update([
                'work_order_id' => $workOrder->id,
                'status' => Appointment::STATUS_CONVERTED,
            ]);

            $workOrder->logActivity(
                'appointment_converted',
                __('work_orders.activities.actions.appointment_converted', [
                    'code' => $workOrder->code,
                    'time' => $locked->scheduled_at?->toDateTimeString() ?? '',
                ])
            );

            return $workOrder;
        });
    }
}
