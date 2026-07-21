<?php

declare(strict_types=1);

use App\Models\CompanyTransaction;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';

$request = Request::create('/', 'GET');
$app->instance('request', $request);

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::first();
auth()->login($user);

echo "=== All Company Transactions ===\n";
$txs = CompanyTransaction::all();

foreach ($txs as $t) {
    echo "ID: {$t->id}, Title: {$t->title}, Type: {$t->transaction_type}, Status: {$t->status}, IsTaxable: ".($t->is_taxable ? 'YES' : 'NO').', Sales Invoice ID: '.($t->invoice_id ?? 'NULL').', Purchase Invoice ID: '.($t->purchase_invoice_id ?? 'NULL')."\n";
}
