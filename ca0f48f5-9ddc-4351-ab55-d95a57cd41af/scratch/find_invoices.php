<?php

use App\Models\CompanyTransaction;
use App\Models\User;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';

$request = Illuminate\Http\Request::create('/', 'GET');
$app->instance('request', $request);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = User::first();
auth()->login($user);

echo "=== All Company Transactions ===\n";
$txs = CompanyTransaction::all();
foreach ($txs as $t) {
    echo "ID: {$t->id}, Title: {$t->title}, Type: {$t->transaction_type}, Status: {$t->status}, IsTaxable: " . ($t->is_taxable ? 'YES' : 'NO') . ", Sales Invoice ID: " . ($t->invoice_id ?? 'NULL') . ", Purchase Invoice ID: " . ($t->purchase_invoice_id ?? 'NULL') . "\n";
}
