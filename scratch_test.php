<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$suppliers = \App\Models\Supplier::all();

foreach ($suppliers as $supplier) {
    $invoicesTotal = $supplier->purchaseInvoices()
        ->whereNotIn('status', [\App\Models\PurchaseInvoice::STATUS_DRAFT, \App\Models\PurchaseInvoice::STATUS_CANCELLED])
        ->sum('total');

    $paymentsTotal = $supplier->payments()
        ->where('type', \App\Models\Payment::TYPE_PAYMENT)
        ->whereHas('purchaseInvoice', function($q) {
            $q->whereNotIn('status', [\App\Models\PurchaseInvoice::STATUS_DRAFT, \App\Models\PurchaseInvoice::STATUS_CANCELLED]);
        })
        ->sum('amount');

    $returnsTotal = \App\Models\PurchaseReturnInvoice::whereHas('purchaseInvoice', function($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id)
              ->whereNotIn('status', [\App\Models\PurchaseInvoice::STATUS_DRAFT, \App\Models\PurchaseInvoice::STATUS_CANCELLED]);
        })
        ->sum('total');

    $refundsTotal = $supplier->payments()
        ->where('type', \App\Models\Payment::TYPE_REFUND)
        ->whereHas('purchaseInvoice', function($q) {
            $q->whereNotIn('status', [\App\Models\PurchaseInvoice::STATUS_DRAFT, \App\Models\PurchaseInvoice::STATUS_CANCELLED]);
        })
        ->sum('amount');

    $calculated = (float)$invoicesTotal - (float)$paymentsTotal - (float)$returnsTotal + (float)$refundsTotal;
    $oldBalance = $supplier->purchaseInvoices()
        ->whereNotIn('status', ['draft', 'cancelled'])
        ->sum('balance');
        
    echo "Supplier: {$supplier->name} (ID: {$supplier->id})\n";
    echo "  Invoices: {$invoicesTotal}\n";
    echo "  Payments: {$paymentsTotal}\n";
    echo "  Returns:  {$returnsTotal}\n";
    echo "  Refunds:  {$refundsTotal}\n";
    echo "  Calculated Balance: {$calculated}\n";
    echo "  Old Balance: {$oldBalance}\n\n";
}
