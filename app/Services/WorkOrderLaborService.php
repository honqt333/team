<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Owns the lifecycle of WorkOrderLaborEntry sessions.
 *
 * Rules implemented here (single source of truth for both the
 * controller and the tests):
 *
 *   start  - a user can only have one RUNNING session per tenant.
 *            the technician must be assigned to the item.
 *            the item must not be completed or cancelled.
 *            starting the first session flips the item to
 *            in_progress if it was pending; on_hold items are left
 *            alone (the user must explicitly resume the WO).
 *
 *   pause  - only a running session can be paused. The pause
 *            optionally records a reason. started_at and resumed_at
 *            are untouched so the live duration math keeps working.
 *
 *   resume - only a paused session can be resumed. We accumulate
 *            (now - paused_at) into paused_duration_seconds and
 *            clear paused_at. resumed_at is set to now() so the
 *            next "running" interval is anchored correctly.
 *
 *   end    - running OR paused can be ended. ended_at is set, and
 *            if the session was paused, the unaccumulated tail
 *            (now - paused_at) is folded into paused_duration_seconds
 *            first. The work order item is NEVER auto-completed by
 *            ending a timer — that is a separate decision the user
 *            makes on the work order item itself.
 *
 *   closeOpenSessionsForItem - bulk operation called when an item
 *            is moved to completed or cancelled. Any still-open
 *            session is auto-ended with ended_at = now(). This is
 *            the only way an item status change can affect labor
 *            entries.
 */
class WorkOrderLaborService
{
    /**
     * Start a new labor session for the given user on the given
     * item. Returns the freshly created entry. Throws on rule
     * violations so the controller can map them to HTTP codes.
     *
     * @throws RuntimeException
     */
    public function start(WorkOrder $workOrder, WorkOrderItem $item, User $user, ?string $notes = null): WorkOrderLaborEntry
    {
        $this->assertSameTenantAndCenter($workOrder, $item);
        $this->assertItemIsAssignable($item);

        // Rule: technician must be assigned to this item.
        $isAssigned = $item->technicians()
            ->where('users.id', $user->id)
            ->exists();

        if (! $isAssigned) {
            throw new RuntimeException('technician_not_assigned_to_item');
        }

        return DB::transaction(function () use ($workOrder, $item, $user, $notes) {
            // Lock the user row inside the tenant scope so a
            // concurrent start from the same user can't slip past
            // the "one running per tenant" rule.
            $existing = WorkOrderLaborEntry::query()
                ->forUser($user->id)
                ->running()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new RuntimeException('technician_already_running');
            }

            $now = Carbon::now();

            $entry = WorkOrderLaborEntry::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'work_order_id' => $workOrder->id,
                'work_order_item_id' => $item->id,
                'user_id' => $user->id,
                'started_at' => $now,
                'resumed_at' => $now,
                'paused_duration_seconds' => 0,
                'status' => WorkOrderLaborEntry::STATUS_RUNNING,
                'notes' => $notes,
            ]);

            // First start flips a pending item into in_progress. Items
            // already in_progress / on_hold / ready_for_qc are left
            // alone — the timer is independent of item status.
            if ($item->status === WorkOrderItem::STATUS_PENDING) {
                $item->update(['status' => WorkOrderItem::STATUS_IN_PROGRESS]);
            }

            $workOrder->logActivity(
                'labor_started',
                __('work_orders.activities.actions.labor_started', [
                    'title' => $item->title,
                    'user' => $user->name,
                ])
            );

            return $entry;
        });
    }

    /**
     * Pause a running session.
     *
     * @throws RuntimeException
     */
    public function pause(WorkOrderLaborEntry $entry, ?string $reason = null): WorkOrderLaborEntry
    {
        if (! $entry->isRunning()) {
            throw new RuntimeException('entry_not_running');
        }

        $now = Carbon::now();

        $entry->update([
            'status' => WorkOrderLaborEntry::STATUS_PAUSED,
            'paused_at' => $now,
            'pause_reason' => $reason,
        ]);

        $entry->workOrder->logActivity(
            'labor_paused',
            __('work_orders.activities.actions.labor_paused', [
                'title' => $entry->workOrderItem->title,
                'user' => $entry->user->name,
            ])
        );

        return $entry->refresh();
    }

    /**
     * Resume a paused session. Accumulates the paused interval
     * into paused_duration_seconds.
     *
     * @throws RuntimeException
     */
    public function resume(WorkOrderLaborEntry $entry): WorkOrderLaborEntry
    {
        if (! $entry->isPaused() || ! $entry->paused_at) {
            throw new RuntimeException('entry_not_paused');
        }

        $now = Carbon::now();
        $pausedFor = max(0, $now->getTimestamp() - $entry->paused_at->getTimestamp());

        $entry->update([
            'status' => WorkOrderLaborEntry::STATUS_RUNNING,
            'resumed_at' => $now,
            'paused_at' => null,
            'pause_reason' => null,
            'paused_duration_seconds' => $entry->paused_duration_seconds + $pausedFor,
        ]);

        $entry->workOrder->logActivity(
            'labor_resumed',
            __('work_orders.activities.actions.labor_resumed', [
                'title' => $entry->workOrderItem->title,
                'user' => $entry->user->name,
            ])
        );

        return $entry->refresh();
    }

    /**
     * End a running or paused session. Does NOT complete the
     * work order item — the user does that explicitly elsewhere.
     *
     * @throws RuntimeException
     */
    public function end(WorkOrderLaborEntry $entry, ?string $notes = null): WorkOrderLaborEntry
    {
        if ($entry->isCompleted()) {
            throw new RuntimeException('entry_already_completed');
        }

        $now = Carbon::now();
        $pausedDuration = (int) $entry->paused_duration_seconds;

        // If we end while paused, the time since the last pause
        // was never accumulated yet — fold it in now.
        if ($entry->isPaused() && $entry->paused_at) {
            $pausedFor = max(0, $now->getTimestamp() - $entry->paused_at->getTimestamp());
            $pausedDuration += $pausedFor;
        }

        $entry->update([
            'status' => WorkOrderLaborEntry::STATUS_COMPLETED,
            'ended_at' => $now,
            'paused_at' => null,
            'paused_duration_seconds' => $pausedDuration,
            'notes' => $notes ?? $entry->notes,
        ]);

        $entry->workOrder->logActivity(
            'labor_ended',
            __('work_orders.activities.actions.labor_ended', [
                'title' => $entry->workOrderItem->title,
                'user' => $entry->user->name,
            ])
        );

        return $entry->refresh();
    }

    /**
     * Force-close any still-open session for the given item. Used
     * when the item itself moves to completed or cancelled so we
     * don't leave orphan timers ticking.
     *
     * Returns the number of sessions closed.
     */
    public function closeOpenSessionsForItem(WorkOrderItem $item): int
    {
        return DB::transaction(function () use ($item) {
            $open = WorkOrderLaborEntry::query()
                ->where('work_order_item_id', $item->id)
                ->open()
                ->lockForUpdate()
                ->get();

            $count = 0;
            $now = Carbon::now();

            foreach ($open as $entry) {
                $pausedDuration = (int) $entry->paused_duration_seconds;

                if ($entry->isPaused() && $entry->paused_at) {
                    $pausedFor = max(0, $now->getTimestamp() - $entry->paused_at->getTimestamp());
                    $pausedDuration += $pausedFor;
                }

                $entry->update([
                    'status' => WorkOrderLaborEntry::STATUS_COMPLETED,
                    'ended_at' => $now,
                    'paused_at' => null,
                    'paused_duration_seconds' => $pausedDuration,
                ]);

                $count++;
            }

            return $count;
        });
    }

    /**
     * Total effective work time, in seconds, that has been logged
     * across all sessions (open or closed) for the given item.
     */
    public function totalEffectiveSecondsForItem(WorkOrderItem $item): int
    {
        $entries = WorkOrderLaborEntry::query()
            ->where('work_order_item_id', $item->id)
            ->get();

        $total = 0;

        foreach ($entries as $entry) {
            $total += $entry->effectiveDurationSeconds();
        }

        return $total;
    }

    // ==================== Guards ====================

    private function assertSameTenantAndCenter(WorkOrder $workOrder, WorkOrderItem $item): void
    {
        if ($item->work_order_id !== $workOrder->id) {
            throw new RuntimeException('item_not_in_work_order');
        }

        if ($item->tenant_id !== $workOrder->tenant_id || $item->center_id !== $workOrder->center_id) {
            throw new RuntimeException('cross_tenant_or_center');
        }
    }

    private function assertItemIsAssignable(WorkOrderItem $item): void
    {
        if ($item->status === WorkOrderItem::STATUS_COMPLETED) {
            throw new RuntimeException('item_completed');
        }

        if ($item->status === WorkOrderItem::STATUS_CANCELLED) {
            throw new RuntimeException('item_cancelled');
        }
    }
}
