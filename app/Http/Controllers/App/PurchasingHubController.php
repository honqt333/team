<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
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
            ->count();

        $purchasesCount = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->count();

        return Inertia::render('Purchasing/Hub', [
            'ordersCount' => $ordersCount,
            'salesCount' => $salesCount,
            'purchasesCount' => $purchasesCount,
        ]);
    }
}
