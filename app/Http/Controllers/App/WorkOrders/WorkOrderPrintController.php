<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\Department;
use App\Models\Service;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderPrintController
{
    use AuthorizesRequests;

    /**
     * Print vehicle condition report.
     */
    public function printCondition(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'damageMarks',
            'photos',
            'center',
            'tenant',
        ]);

        if ($workOrder->center) {
            $workOrder->center->append(['logo_light_url', 'logo_dark_url', 'logo_invoice_url', 'stamp_url']);
        }

        return Inertia::render('WorkOrders/Print/Condition', [
            'workOrder' => $workOrder,
        ]);
    }

    /**
     * Print work order services list.
     */
    public function printServices(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.technicians.employee.jobTitle',
            'center',
            'tenant',
        ]);

        // Filter out cancelled items
        $filteredItems = $workOrder->items->filter(fn ($item) => $item->status !== WorkOrderItem::STATUS_CANCELLED);
        $workOrder->setRelation('items', $filteredItems->values());

        $departmentId = request('department_id');
        $printedDepartment = null;

        if ($departmentId !== null) {
            $filteredItems = $workOrder->items->filter(function ($item) use ($departmentId) {
                $itemDeptId = $item->service?->type === Service::TYPE_PACKAGE
                    ? 'packages'
                    : ($item->department_id ?? $item->service?->department_id ?? 0);

                return (string) $itemDeptId === (string) $departmentId;
            });
            $workOrder->setRelation('items', $filteredItems->values());

            if ($departmentId === 'packages') {
                $printedDepartment = ['name_ar' => 'باقات الخدمات', 'name_en' => 'Service Packages'];
            } elseif ($departmentId === '0') {
                $printedDepartment = ['name_ar' => 'خدمات غير مصنفة', 'name_en' => 'Uncategorized Services'];
            } else {
                $dept = Department::find($departmentId);

                if ($dept) {
                    $printedDepartment = ['name_ar' => $dept->name_ar, 'name_en' => $dept->name_en];
                }
            }
        }

        $technicianId = request('technician_id');
        $printedTechnician = null;

        if ($technicianId !== null) {
            $filteredItems = $workOrder->items->filter(function ($item) use ($technicianId) {
                return $item->technicians->contains('id', $technicianId);
            });
            $workOrder->setRelation('items', $filteredItems->values());

            $user = User::with('employee')->find($technicianId);

            if ($user) {
                $printedTechnician = [
                    'name_ar' => $user->employee?->name_ar ?: $user->name,
                    'name_en' => $user->employee?->name_en ?: $user->name,
                ];
            }
        }

        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            if ($item->service?->type === Service::TYPE_PACKAGE) {
                return 'packages';
            }

            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        $departments = Department::active()->ordered()->get()->keyBy('id');

        return Inertia::render('WorkOrders/Print/Services', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'departments' => $departments,
            'printedDepartment' => $printedDepartment,
            'printedTechnician' => $printedTechnician,
        ]);
    }

    /**
     * Print proforma invoice.
     */
    public function printProforma(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.parts.part',
            'items.parts.warehouse',
            'parts.part',
            'parts.warehouse',
            'center',
            'tenant',
        ]);

        // Filter out cancelled items
        $filteredItems = $workOrder->items->filter(fn ($item) => $item->status !== WorkOrderItem::STATUS_CANCELLED);
        $workOrder->setRelation('items', $filteredItems->values());

        // Filter out cancelled / reversed parts (both item-level and order-level)
        $activeItemIds = $filteredItems->pluck('id')->all();
        $activeParts = $workOrder->parts->filter(function ($part) use ($activeItemIds) {
            if (in_array($part->status, [WorkOrderItemPart::STATUS_CANCELLED, WorkOrderItemPart::STATUS_REVERSED])) {
                return false;
            }

            if ($part->work_order_item_id !== null && ! in_array($part->work_order_item_id, $activeItemIds)) {
                return false;
            }

            return true;
        })->values();

        $workOrder->append(['total', 'total_discount']);

        $servicesTotal = $filteredItems->sum(function ($item) {
            return $item->line_total ?? ($item->qty * $item->unit_price);
        });

        $partsTotal = $activeParts->sum(function ($part) {
            return ((float) $part->unit_price * (float) $part->qty) - (float) ($part->discount ?? 0);
        });

        $totalPaid = $workOrder->total_paid ?? 0;
        $grandTotal = $servicesTotal + $partsTotal;
        $balance = $grandTotal - $totalPaid;

        $itemsByDepartment = $filteredItems->groupBy(function ($item) {
            if ($item->service?->type === Service::TYPE_PACKAGE) {
                return 'packages';
            }

            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        $departments = Department::active()->ordered()->get()->keyBy('id');
        $taxSettings = TenantTaxSetting::where('tenant_id', $workOrder->tenant_id)->first();

        return Inertia::render('WorkOrders/Print/Proforma', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'allParts' => $activeParts,
            'departments' => $departments,
            'servicesTotal' => $servicesTotal,
            'partsTotal' => $partsTotal,
            'grandTotal' => $grandTotal,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'taxSettings' => $taxSettings,
        ]);
    }

    /**
     * Print payments receipt.
     */
    public function printPayments(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'center',
            'tenant',
        ]);

        $payments = $workOrder->payments()->with('receivedBy')->orderByDesc('payment_date')->get();

        $servicesTotal = $workOrder->items->sum(function ($item) {
            return $item->line_total ?? ($item->qty * $item->unit_price);
        });

        $partsTotal = $workOrder->items->sum(function ($item) {
            return $item->parts?->sum(function ($part) {
                return $part->qty * $part->unit_price;
            }) ?? 0;
        });

        $grandTotal = $servicesTotal + $partsTotal;
        $badDebt = $payments->where('type', 'bad_debt')->sum('amount');
        $totalPaid = $payments->sum('amount');
        $balance = $grandTotal - $totalPaid;

        return Inertia::render('WorkOrders/Print/Payments', [
            'workOrder' => $workOrder,
            'payments' => $payments,
            'grandTotal' => $grandTotal,
            'totalPaid' => $totalPaid,
            'badDebt' => $badDebt,
            'balance' => $balance,
        ]);
    }
}
