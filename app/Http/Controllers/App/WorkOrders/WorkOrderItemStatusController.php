<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\WorkOrderItemQualityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WorkOrderItemStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly WorkOrderItemQualityService $service) {}

    /**
     * Put the item on hold with a mandatory reason.
     */
    public function hold(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'reason' => 'required|string|min:2|max:500',
        ]);

        try {
            $item = $this->service->hold(
                $work_order,
                $item,
                $request->user(),
                $validated['reason'],
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'item_held', $item);
    }

    /**
     * Lift the hold. The actor is ALWAYS the current user; clients
     * can never resume on behalf of someone else.
     */
    public function resume(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        try {
            $item = $this->service->resume(
                $work_order,
                $item,
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'item_resumed', $item);
    }

    /**
     * Approve the item: marks the QC pass and flips the item to
     * `completed`. The existing observer closes any open labor
     * session.
     */
    public function approveQuality(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $check = $this->service->approveQuality(
                $work_order,
                $item,
                $request->user(),
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'item_quality_approved', $check);
    }

    /**
     * Reject the item. Notes are mandatory because the technician
     * needs to know what to redo.
     */
    public function rejectQuality(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'notes' => 'required|string|min:2|max:1000',
        ]);

        try {
            $check = $this->service->rejectQuality(
                $work_order,
                $item,
                $request->user(),
                $validated['notes'],
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'item_quality_rejected', $check);
    }

    // ==================== Helpers ====================

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
