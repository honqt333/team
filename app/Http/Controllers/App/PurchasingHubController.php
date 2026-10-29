<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingHubController extends Controller
{
    /**
     * Display the purchasing/store hub page.
     *
     * @return Response
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $ordersCount = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->count();

        $salesCount = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNull('work_order_id')
            ->where(function ($q) {
                $q->where('type', 'invoice')
                    ->orWhere('type', InvoiceType::INVOICE)
                    ->orWhereNull('type');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.invoice_id', 'invoices.id');
            })
            ->count();

        $purchasesCount = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.purchase_invoice_id', 'purchase_invoices.id');
            })
            ->count();

        return Inertia::render('Purchasing/Hub', [
            'ordersCount' => $ordersCount,
            'salesCount' => $salesCount,
            'purchasesCount' => $purchasesCount,
        ]);
    }
}
