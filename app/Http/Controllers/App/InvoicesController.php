<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * List all invoices
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $query = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['customer', 'workOrder'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                          ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('payment_status'), fn($q, $status) => $q->where('payment_status', $status))
            ->when($request->input('date_from'), fn($q, $date) => $q->whereDate('issue_date', '>=', $date))
            ->when($request->input('date_to'), fn($q, $date) => $q->whereDate('issue_date', '<=', $date))
            ->orderBy('issue_date', 'desc');

        $invoices = $query->paginate(25)->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'payment_status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show single invoice
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'customer',
            'workOrder.vehicle',
            'lines',
            'payments.receivedBy',
            'center',
        ]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Generate invoice from work order
     */
    public function createFromWorkOrder(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        // Check if already has invoice
        if ($workOrder->invoice) {
            return back()->with('error', __('invoices.already_exists'));
        }

        // Check work order status
        if (!in_array($workOrder->status, ['completed', 'delivered'])) {
            return back()->with('error', __('invoices.work_order_not_completed'));
        }

        try {
            $invoice = $this->invoiceService->createFromWorkOrder($workOrder, auth()->user());
            $this->invoiceService->issueInvoice($invoice);

            return redirect()->route('app.invoices.show', $invoice->id)
                ->with('success', __('invoices.created'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print invoice view
     */
    public function print(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'customer',
            'workOrder.vehicle',
            'lines',
            'center',
            'tenant.taxSettings',
        ]);

        $template = \App\Models\InvoiceTemplate::getDefault($invoice->tenant_id, 'tax_invoice');

        return Inertia::render('Invoices/Print', [
            'invoice' => $invoice,
            'template' => $template,
            'labels' => $template->getAllLabels(),
        ]);
    }

    /**
     * Print proforma from work order
     */
    public function printProforma(WorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);

        $data = $this->invoiceService->getProformaData($workOrder);

        return Inertia::render('Invoices/PrintProforma', $data);
    }
}
