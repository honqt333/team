<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseInvoicesController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $query = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['supplier', 'purchaseOrder'])
            ->when($request->input('search'), fn($q, $s) =>
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('invoice_number', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$s}%"))
            )
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('date_from'), fn($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderBy('created_at', 'desc');

        return Inertia::render('Purchasing/Invoices/Index', [
            'invoices' => $query->paginate(25)->withQueryString(),
            'filters'  => $request->only(['search', 'status', 'date_from', 'date_to']),
            'stats'    => [
                'total'  => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->count(),
                'open'   => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'open')->count(),
                'paid'   => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'paid')->count(),
                'draft'  => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'draft')->count(),
            ],
        ]);
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['supplier', 'purchaseOrder', 'lines.part']);

        return Inertia::render('Purchasing/Invoices/Show', [
            'invoice' => $purchaseInvoice,
        ]);
    }
}
