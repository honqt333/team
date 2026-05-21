<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchasingHubController extends Controller
{
    /**
     * Display the purchasing/store hub page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $ordersCount = \App\Models\PurchaseOrder::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->count();

        $salesCount = \App\Models\Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->count();

        $purchasesCount = \App\Models\PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->count();

        return Inertia::render('Purchasing/Hub', [
            'ordersCount' => $ordersCount,
            'salesCount' => $salesCount,
            'purchasesCount' => $purchasesCount,
        ]);
    }
}
