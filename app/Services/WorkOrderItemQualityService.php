<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemQualityCheck;
use App\Models\WorkOrderLaborEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Owns the per-item hold / resume / quality-check lifecycle.
 *
 * This is intentionally separate from WorkOrderTechnicianController
 * (which only changes `status` to a string). The hold lifecycle
 * captures the *previous* status so resume can restore it; the
 * quality-check flow needs a separate audit table because every
 * decision (approve / reject) is durable history, not a single
 * mutable flag.
 *
 * Hold rule summary (matches the spec):
 *   - Only `pending` / `in_progress` / `ready_for_qc` items can be
 *     held. `completed` and `cancelled` are terminal.
 *   - `hold_reason` is REQUIRED and is recorded on the item.
 *   - `suspended_status` is set to the pre-hold status so resume
 *     can restore it.
 *   - Any `running` labor session for the item is moved to
 *     `paused` with a synthetic pause_reason so the technician
 *     can later resume manually. Closed sessions are not touched.
 *   - Worker timers are NOT auto-resumed on item-resume — only
 *     the item flips back. The technician owns the timer button.
 *
 * Quality-check rule summary:
 *   - Only `ready_for_qc` items can be approved or rejected.
 *   - Approval flips the item to `completed`. The existing
 *     `WorkOrderItemObserver` will close any open labor session.
 *   - Rejection requires a non-empty `notes`, records a row with
 *     `decision = rejected`, and flips the item back to
 *     `in_progress`. No timer is started.
 */
class WorkOrderItemQualityService
{
    /**
     * Put the item on hold with the given reason.
     *
     * @throws RuntimeException
     */
    public function hold(WorkOrder $workOrder, WorkOrderItem $item, User $actor, string $reason): WorkOrderItem
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new RuntimeException('hold_reason_required');
        }

        $this->assertSameContext($workOrder, $item);

        if ($item->status === WorkOrderItem::STATUS_COMPLETED) {
            throw new RuntimeException('item_completed');
        }

        if ($item->status === WorkOrderItem::STATUS_CANCELLED) {
            throw new RuntimeException('item_cancelled');
        }

        if ($item->status === WorkOrderItem::STATUS_ON_HOLD) {
            throw new RuntimeException('item_already_on_hold');
        }

        $now = Carbon::now();

        return DB::transaction(function () use ($workOrder, $item, $actor, $reason, $now) {
            // Capture the pre-hold status so resume can restore it.
            $previousStatus = $item->status;

            $item->update([
                'status' => WorkOrderItem::STATUS_ON_HOLD,
                'suspended_status' => $previousStatus,
                'hold_reason' => $reason,
                'held_at' => $now,
                'held_by_user_id' => $actor->id,
            ]);

            // Move every RUNNING labor session on this item to PAUSED
            // with a reason that points to the hold. We keep the
            // session open (ended_at stays NULL) so the technician
            // can resume it manually after the item itself resumes.
            $this->pauseRunningSessionsForItem(
                $item,
                __('work_orders.labor.paused_due_to_hold', ['reason' => $reason])
            );

            $workOrder->logActivity(
                'item_held',
                __('work_orders.activities.actions.item_held', [
                    'title' => $item->title,
                    'reason' => $reason,
                    'user' => $actor->name,
                ])
            );

            return $item->refresh();
        });
    }

    /**
     * Lift the hold. The item goes back to its captured
     * `suspended_status` if that is still a valid active state,
     * otherwise falls back to `in_progress`.
     *
     * @throws RuntimeException
     */
    public function resume(WorkOrder $workOrder, WorkOrderItem $item, User $actor): WorkOrderItem
    {
        $this->assertSameContext($workOrder, $item);

        if ($item->status !== WorkOrderItem::STATUS_ON_HOLD) {
            throw new RuntimeException('item_not_on_hold');
        }

        $restored = $this->resolveResumeTarget($item->suspended_status);

        return DB::transaction(function () use ($workOrder, $item, $actor, $restored) {
            $item->update([
                'status' => $restored,
                'suspended_status' => null,
                'hold_reason' => null,
                'held_at' => null,
                'held_by_user_id' => null,
            ]);

            $workOrder->logActivity(
                'item_resumed',
                __('work_orders.activities.actions.item_resumed', [
                    'title' => $item->title,
                    'user' => $actor->name,
                ])
            );

            return $item->refresh();
        });
    }

    /**
     * Approve the item: the work is good. Item → completed.
     *
     * @throws RuntimeException
     */
    public function approveQuality(
        WorkOrder $workOrder,
        WorkOrderItem $item,
        User $actor,
        ?string $notes = null,
    ): WorkOrderItemQualityCheck {
        $this->assertSameContext($workOrder, $item);

        if ($item->status !== WorkOrderItem::STATUS_READY_FOR_QC) {
            throw new RuntimeException('item_not_ready_for_qc');
        }

        return DB::transaction(function () use ($workOrder, $item, $actor, $notes) {
            $check = WorkOrderItemQualityCheck::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'work_order_id' => $workOrder->id,
                'work_order_item_id' => $item->id,
                'checked_by_user_id' => $actor->id,
                'decision' => WorkOrderItemQualityCheck::DECISION_APPROVED,
                'notes' => $notes,
                'checked_at' => Carbon::now(),
            ]);

            $item->update(['status' => WorkOrderItem::STATUS_COMPLETED]);

            $workOrder->logActivity(
                'item_quality_approved',
                __('work_orders.activities.actions.item_quality_approved', [
                    'title' => $item->title,
                    'user' => $actor->name,
                ])
            );

            return $check;
        });
    }

    /**
     * Reject the item: the work needs to be redone. Item →
     * in_progress. The technician (or anyone assigned) can
     * manually start / resume a timer afterwards.
     *
     * @throws RuntimeException
     */
    public function rejectQuality(
        WorkOrder $workOrder,
        WorkOrderItem $item,
        User $actor,
        string $notes,
    ): WorkOrderItemQualityCheck {
        $notes = trim($notes);

        if ($notes === '') {
            throw new RuntimeException('rejection_notes_required');
        }

        $this->assertSameContext($workOrder, $item);

        if ($item->status !== WorkOrderItem::STATUS_READY_FOR_QC) {
            throw new RuntimeException('item_not_ready_for_qc');
        }

        return DB::transaction(function () use ($workOrder, $item, $actor, $notes) {
            $check = WorkOrderItemQualityCheck::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'work_order_id' => $workOrder->id,
                'work_order_item_id' => $item->id,
                'checked_by_user_id' => $actor->id,
                'decision' => WorkOrderItemQualityCheck::DECISION_REJECTED,
                'notes' => $notes,
                'checked_at' => Carbon::now(),
            ]);

            $item->update(['status' => WorkOrderItem::STATUS_IN_PROGRESS]);

            $workOrder->logActivity(
                'item_quality_rejected',
                __('work_orders.activities.actions.item_quality_rejected', [
                    'title' => $item->title,
                    'reason' => $notes,
                    'user' => $actor->name,
                ])
            );

            return $check;
        });
    }

    // ==================== Helpers ====================

    private function assertSameContext(WorkOrder $workOrder, WorkOrderItem $item): void
    {
        if ($item->work_order_id !== $workOrder->id) {
            throw new RuntimeException('item_not_in_work_order');
        }

        if ($item->tenant_id !== $workOrder->tenant_id || $item->center_id !== $workOrder->center_id) {
            throw new RuntimeException('cross_tenant_or_center');
        }
    }

    /**
     * Convert the captured `suspended_status` into a safe resume
     * target. Anything terminal (completed/cancelled) is replaced
     * with `in_progress`. Anything invalid (e.g. an old enum that
     * was removed) also falls back to `in_progress`.
     */
    private function resolveResumeTarget(?string $previous): string
    {
        $valid = [
            WorkOrderItem::STATUS_PENDING,
            WorkOrderItem::STATUS_IN_PROGRESS,
            WorkOrderItem::STATUS_ON_HOLD,
            WorkOrderItem::STATUS_READY_FOR_QC,
        ];

        if ($previous !== null && in_array($previous, $valid, true)) {
            return $previous;
        }

        return WorkOrderItem::STATUS_IN_PROGRESS;
    }

    /**
     * Move every RUNNING labor session on the item to PAUSED with
     * the given reason. Closed sessions are left alone. We do not
     * set `ended_at` — these are paused, not closed, so the
     * technician can resume them later.
     */
    private function pauseRunningSessionsForItem(WorkOrderItem $item, string $reason): int
    {
        $now = Carbon::now();
        $count = 0;

        $open = WorkOrderLaborEntry::query()
            ->where('work_order_item_id', $item->id)
            ->where('status', WorkOrderLaborEntry::STATUS_RUNNING)
            ->get();

        foreach ($open as $session) {
            $session->update([
                'status' => WorkOrderLaborEntry::STATUS_PAUSED,
                'paused_at' => $now,
                'pause_reason' => $reason,
            ]);
            $count++;
        }

        return $count;
    }
}
