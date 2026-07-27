<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use App\Services\WorkOrderLaborService;
use App\Support\Permissions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WorkOrderLaborEntryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly WorkOrderLaborService $service) {}

    /**
     * Start a labor session for the current user on the given item.
     * The technician id is ALWAYS the current user — clients can
     * never start a timer in someone else's name.
     */
    public function start(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $entry = $this->service->start(
                $work_order,
                $item,
                $request->user(),
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            'labor_started',
            $entry->fresh()->load(['user', 'workOrderItem']),
        );
    }

    /**
     * Pause a running session. The current user MUST own the
     * session; only the assigned technician (or a privileged admin)
     * can pause it.
     */
    public function pause(Request $request, WorkOrder $work_order, WorkOrderLaborEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);
        $this->ensureSameTenantAndCenter($work_order, $entry);
        $this->ensureOwnsEntryOrIsAdmin($request, $entry);

        $validated = $request->validate([
            'pause_reason' => 'nullable|string|max:255',
        ]);

        try {
            $entry = $this->service->pause($entry, $validated['pause_reason'] ?? null);
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            'labor_paused',
            $entry->fresh()->load(['user', 'workOrderItem']),
        );
    }

    /**
     * Resume a paused session. Same ownership rules as pause.
     */
    public function resume(Request $request, WorkOrder $work_order, WorkOrderLaborEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);
        $this->ensureSameTenantAndCenter($work_order, $entry);
        $this->ensureOwnsEntryOrIsAdmin($request, $entry);

        try {
            $entry = $this->service->resume($entry);
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            'labor_resumed',
            $entry->fresh()->load(['user', 'workOrderItem']),
        );
    }

    /**
     * End a running or paused session. Ownership rule applies.
     */
    public function end(Request $request, WorkOrder $work_order, WorkOrderLaborEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);
        $this->ensureSameTenantAndCenter($work_order, $entry);
        $this->ensureOwnsEntryOrIsAdmin($request, $entry);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $entry = $this->service->end($entry, $validated['notes'] ?? null);
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            'labor_ended',
            $entry->fresh()->load(['user', 'workOrderItem']),
        );
    }

    // ==================== Helpers ====================

    /**
     * Defense in depth — the CenterScoped trait on the model adds a
     * global scope, but route model binding for {work_order} might
     * not catch a center mismatch for the labor entry. Re-check
     * here.
     */
    private function ensureSameTenantAndCenter(WorkOrder $workOrder, WorkOrderLaborEntry $entry): void
    {
        abort_unless(
            $entry->work_order_id === $workOrder->id
            && $entry->tenant_id === $workOrder->tenant_id
            && $entry->center_id === $workOrder->center_id,
            404
        );
    }

    /**
     * A user can only act on sessions they own. The work-order
     * `update` permission (used above) is the basic gate; for
     * session-level control we additionally require the user to be
     * the entry's owner — UNLESS they have a work-order admin
     * override permission already established in the project
     * (`crm.work_orders.update` plus admin role).
     */
    private function ensureOwnsEntryOrIsAdmin(Request $request, WorkOrderLaborEntry $entry): void
    {
        $user = $request->user();

        if ($entry->user_id === $user->id) {
            return;
        }

        // Admins are defined by the existing project convention:
        // holding crm.work_orders.update AND being a system role.
        $isAdmin = $user->can(Permissions::WORK_ORDERS_UPDATE)
            && ($user->hasRole('super_admin') || $user->hasRole('admin'));

        abort_unless($isAdmin, 403);
    }

    private function errorResponse(Request $request, string $messageKey, int $status): RedirectResponse|JsonResponse
    {
        $message = __("messages.{$messageKey}");

        if ($message === "messages.{$messageKey}") {
            // Fallback: a translation wasn't installed. Use the key
            // so the dev sees what to localize.
            $message = $messageKey;
        }

        return $request->expectsJson()
            ? response()->json(['error' => $message, 'code' => $messageKey], $status)
            : redirect()->back()->with('error', $message);
    }

    private function successResponse(Request $request, string $messageKey, WorkOrderLaborEntry $entry): RedirectResponse|JsonResponse
    {
        $message = __("messages.{$messageKey}");

        if ($message === "messages.{$messageKey}") {
            $message = $messageKey;
        }

        return $request->expectsJson()
            ? response()->json(['success' => $message, 'entry' => $entry])
            : redirect()->back()->with('success', $message);
    }
}
