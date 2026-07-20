<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkOrderStatusController
{
    use AuthorizesRequests;

    /**
     * Update condition report (fuel level and damage marks).
     */
    public function updateCondition(\App\Http\Requests\WorkOrder\UpdateConditionRequest $request, WorkOrder $workOrder): mixed
    {
        $this->authorize('update', $workOrder);

        if (!$workOrder->canBeEdited()) {
            return back()->withErrors(['error' => __('messages.work_order_locked')]);
        }

        $validated = $request->validated();

        $hasChanges = false;
        
        if ($workOrder->fuel_level != ($validated['fuel_level'] ?? $workOrder->fuel_level)) {
            $hasChanges = true;
            $workOrder->update([
                'fuel_level' => $validated['fuel_level'],
            ]);
        }

        // Update damage marks through relationship
        if (isset($validated['damage_marks'])) {
            $oldMarks = $workOrder->damageMarks->map(fn($m) => [
                'x' => round((float)$m->x, 2),
                'y' => round((float)$m->y, 2),
                'color' => $m->color,
                'description' => $m->description ?? '',
            ])->values()->toArray();

            $newMarks = collect($validated['damage_marks'])->map(fn($m) => [
                'x' => round((float)($m['x'] ?? 0), 2),
                'y' => round((float)($m['y'] ?? 0), 2),
                'color' => $m['color'] ?? 'red',
                'description' => $m['description'] ?? '',
            ])->values()->toArray();

            if ($oldMarks !== $newMarks) {
                $hasChanges = true;
                $workOrder->damageMarks()->delete();
                foreach ($validated['damage_marks'] as $mark) {
                    $workOrder->damageMarks()->create([
                        'x' => $mark['x'] ?? 0,
                        'y' => $mark['y'] ?? 0,
                        'color' => $mark['color'] ?? 'red',
                        'description' => $mark['description'] ?? '',
                    ]);
                }
            }
        }

        if ($hasChanges) {
            $workOrder->logActivity('condition_updated', __('work_orders.activities.actions.condition_updated'));
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Start work on a work order.
     * Transition pending services to in_progress.
     */
    public function startWork(WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);

        if ($work_order->status !== WorkOrder::STATUS_OPEN && $work_order->status !== WorkOrder::STATUS_IN_PROGRESS) {
            return redirect()->back()->with('error', __('messages.cannot_start_work') ?? 'لا يمكن بدء العمل على كرت بهذه الحالة');
        }

        if ($work_order->items()->count() === 0) {
            return redirect()->back()->with('error', __('messages.cannot_start_work_no_services'));
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($work_order) {
            $work_order->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);

            foreach ($work_order->items()->where('status', WorkOrderItem::STATUS_PENDING)->get() as $item) {
                $item->update(['status' => WorkOrderItem::STATUS_IN_PROGRESS]);
            }
        });

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.in_progress')]));

        return redirect()->back()->with('success', __('messages.work_order_started') ?? 'تم بدء العمل على كرت الصيانة بنجاح وتحويل الخدمات إلى جاري');
    }

    /**
     * Put work order on hold.
     */
    public function putOnHold(\App\Http\Requests\WorkOrder\ChangeStatusRequest $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validated();

        if (!$work_order->canBeOnHold()) {
            return redirect()->back()->with('error', __('messages.cannot_put_on_hold'));
        }

        $work_order->putOnHold($validated['reason']);

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed_on_hold', ['reason' => $validated['reason']]) ?? ('تم تعليق كرت العمل بسبب: ' . $validated['reason']));

        return redirect()->back()->with('success', __('messages.work_order_on_hold'));
    }

    /**
     * Resume work order from hold.
     */
    public function resume(WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('resume', $work_order);

        if (!$work_order->resume()) {
            return redirect()->back()->with('error', __('messages.cannot_resume'));
        }

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.in_progress')]));

        return redirect()->back()->with('success', __('messages.work_order_resumed'));
    }

    /**
     * Cancel work order.
     */
    public function cancel(WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('cancel', $work_order);

        if (!$work_order->canBeCancelled()) {
            return redirect()->back()->with('error', __('messages.cannot_cancel_has_technicians_or_parts'));
        }

        $work_order->update(['status' => WorkOrder::STATUS_CANCELLED]);

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.cancelled')]));

        return redirect()->back()->with('success', __('messages.work_order_cancelled'));
    }

    /**
     * Mark as completed (vehicle exit).
     */
    public function complete(Request $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'exit_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'is_deferred' => 'nullable|boolean',
            'due_date' => 'required_if:is_deferred,true|nullable|date|after_or_equal:exit_date',
        ]);

        if (!$work_order->allItemsCompleted()) {
            return redirect()->route('work-orders.show', $work_order->id)->with('error', __('messages.cannot_complete_items_pending') ?? 'لا يمكن إكمال العمل لوجود خدمات قيد الانتظار أو قيد التنفيذ');
        }

        if ($work_order->balance < -0.01) {
            return redirect()->route('work-orders.show', $work_order->id)->with('error', __('messages.cannot_complete_excess_payments') ?? 'لا يمكن تسجيل خروج المركبة لوجود مبالغ زائدة مدفوعة لم تسترجع! يرجى رد المبلغ الزائد أولاً.');
        }

        $exitDate = Carbon::parse($validated['exit_date']);
        $notes = $work_order->notes;
        if (!empty($validated['notes'])) {
            $notes = trim(($notes ? $notes . "\n" : '') . "ملاحظات الخروج: " . $validated['notes']);
        }

        if (!$work_order->markAsCompleted($exitDate, $notes)) {
            return redirect()->route('work-orders.show', $work_order->id)->with('error', __('messages.cannot_complete_error') ?? 'حدث خطأ أثناء محاولة إكمال كرت العمل');
        }

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.done')]));

        $work_order = $work_order->fresh(['payments', 'invoice', 'items', 'parts']);

        if (!$work_order->invoice && ($work_order->balance <= 0 || $request->boolean('is_deferred'))) {
            try {
                $invoiceService = app(InvoiceService::class);
                $invoice = $invoiceService->createFromWorkOrder($work_order, auth()->user());

                if ($request->boolean('is_deferred') && $request->date('due_date')) {
                    $invoice->due_date = $request->date('due_date');
                    $invoice->save();
                }

                $invoiceService->issueInvoice($invoice);

                NotificationService::notifyOwner(
                    tenantId: auth()->user()->tenant_id,
                    type: 'invoice.created',
                    title: 'فاتورة جديدة #' . $invoice->invoice_number,
                    body: 'تم إنشاء فاتورة من أمر العمل #' . ($work_order->code ?? $work_order->id),
                    actionUrl: '/app/invoices/' . $invoice->id,
                    actorId: auth()->id(),
                );

                return redirect()->route('app.invoices.show', $invoice->id)
                    ->with('success', __('messages.work_order_completed_and_invoice_issued') ?? 'تم خروج المركبة بنجاح وإصدار الفاتورة');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        return redirect()->route('work-orders.show', $work_order->id)->with('success', __('messages.work_order_completed'));
    }
}
