<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\AI\WorkOrderAiInvalidResponseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrder\WorkOrderSuggestionRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AI\WorkOrderSuggestionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * Thin controller for the WorkOrder AI suggester endpoint.
 *
 * Pipeline:
 *   - Route model binding + `WorkOrder::resolveRouteBinding` enforce
 *     tenant isolation automatically (cross-tenant → 404).
 *   - This controller then:
 *       1. authorizes `update` on the bound work order
 *       2. fail-closes if the user has no `current_center_id`
 *       3. delegates to `WorkOrderSuggestionService::suggest()`
 *       4. maps known exceptions to HTTP failure codes (§8 of the
 *          contract)
 *
 * Route: `POST /api/v1/work-orders/{workOrder}/suggestions`
 *
 * Spec: docs/features/ai-service-suggester/design.md §2, §8.
 */
class WorkOrderSuggestionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkOrderSuggestionService $suggestions,
    ) {}

    public function suggest(
        WorkOrder $workOrder,
        WorkOrderSuggestionRequest $request,
    ): JsonResponse {
        $this->authorize('update', $workOrder);

        /** @var User|null $user */
        $user = $request->user();
        $tenantId = (int) ($user?->tenant_id ?? 0);
        $centerId = (int) ($user?->current_center_id ?? 0);

        if ($tenantId <= 0) {
            return response()->json([
                'message' => __('work_orders.suggestions.errors.tenant_required'),
            ], 403);
        }

        if ($centerId <= 0) {
            return response()->json([
                'message' => __('work_orders.suggestions.errors.center_required'),
            ], 403);
        }

        try {
            $payload = $this->suggestions->suggest(
                workOrder: $workOrder,
                request: $request,
                user: $user,
                tenantId: $tenantId,
                centerId: $centerId,
            );
        } catch (WorkOrderAiInvalidResponseException $e) {
            // 502 — see contract §8
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json($payload);
    }
}
