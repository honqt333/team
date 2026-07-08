<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\WorkOrderItem;
use App\Support\TenancyContext;
use Illuminate\Http\JsonResponse;

class WorkOrderWarrantiesController
{
    /**
     * Get active warranties for a specific vehicle.
     */
    public function activeWarranties($vehicle_id): JsonResponse
    {
        $tenantId = TenancyContext::tenantId();

        // Find all completed work order items for this vehicle where warranty is still active (across all branches under same tenant)
        $activeWarranties = WorkOrderItem::withoutGlobalScope('center_scoped')
            ->with(['service', 'workOrder' => function ($q) {
                $q->withoutGlobalScope('center_scoped')->with('center');
            }])
            ->where('tenant_id', $tenantId)
            ->whereHas('workOrder', function ($q) use ($vehicle_id, $tenantId) {
                $q->withoutGlobalScope('center_scoped')
                  ->where('tenant_id', $tenantId)
                  ->where('vehicle_id', $vehicle_id)
                  ->where('status', 'done'); // completed work orders
            })
            ->where('status', WorkOrderItem::STATUS_COMPLETED)
            ->whereNotNull('warranty_expires_at')
            ->where('warranty_expires_at', '>', now())
            ->get()
            ->map(function ($item) use ($vehicle_id, $tenantId) {
                // Find previous cards for this vehicle where this service was done under warranty (across all branches under same tenant)
                $claims = WorkOrderItem::withoutGlobalScope('center_scoped')
                    ->with(['workOrder' => function ($q) {
                        $q->withoutGlobalScope('center_scoped')->with('center');
                    }])
                    ->where('tenant_id', $tenantId)
                    ->whereHas('workOrder', function ($q) use ($vehicle_id, $tenantId) {
                        $q->withoutGlobalScope('center_scoped')
                          ->where('tenant_id', $tenantId)
                          ->where('vehicle_id', $vehicle_id)
                          ->where('status', 'done');
                    })
                    ->where('is_warranty', true)
                    ->where('title', $item->title)
                    ->get()
                    ->map(function ($claim) {
                        return [
                            'work_order_code' => $claim->workOrder?->code,
                            'work_order_id' => $claim->work_order_id,
                            'service_date' => $claim->workOrder?->created_at?->format('Y-m-d'),
                            'center_name_ar' => $claim->workOrder?->center?->name_ar,
                            'center_name_en' => $claim->workOrder?->center?->name_en,
                        ];
                    });

                return [
                    'id' => $item->id,
                    'service_id' => $item->service_id,
                    'service_name_ar' => $item->service?->name_ar ?? $item->title,
                    'service_name_en' => $item->service?->name_en ?? $item->title,
                    'title' => $item->title,
                    'warranty_expires_at' => $item->warranty_expires_at->toIso8601String(),
                    'warranty_value_snapshot' => $item->warranty_value_snapshot,
                    'warranty_unit_snapshot' => $item->warranty_unit_snapshot,
                    'work_order_code' => $item->workOrder?->code,
                    'work_order_id' => $item->work_order_id,
                    'unit_price' => $item->unit_price,
                    'final_unit_price' => $item->final_unit_price,
                    'service_date' => $item->workOrder?->created_at?->format('Y-m-d'),
                    'center_name_ar' => $item->workOrder?->center?->name_ar,
                    'center_name_en' => $item->workOrder?->center?->name_en,
                    'claims' => $claims,
                ];
            });

        return response()->json([
            'active_warranties' => $activeWarranties
        ]);
    }
}
