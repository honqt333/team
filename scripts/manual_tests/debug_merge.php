<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

// Enable query log
DB::enableQueryLog();

echo "--- Debugging Customer Merge Query ---" . PHP_EOL;

$count = Customer::count();
echo "Total Customers in DB: " . $count . PHP_EOL;

if ($count < 2) {
    echo "WARNING: Less than 2 customers found. Merge list will be empty!" . PHP_EOL;
    $all = Customer::all(['id', 'name']);
    foreach($all as $c) {
        echo " - ID: {$c->id}, Name: {$c->name}" . PHP_EOL;
    }
    exit;
}

// Pick the first customer as "Source"
$source = Customer::first();
echo "Source Customer: [{$source->id}] {$source->name} (Type: " . ($source->type ?? 'NULL') . ")" . PHP_EOL;

// Run the query logic from controller
$query = Customer::where('id', '!=', $source->id)
    ->withCount(['vehicles', 'quotes', 'workOrders'])
    ->orderBy('name');

// Check SQL
echo "Query SQL: " . $query->toSql() . PHP_EOL;
echo "Query Bindings: " . implode(', ', $query->getBindings()) . PHP_EOL;

$targets = $query->get();

echo "Targets Found: " . $targets->count() . PHP_EOL;

foreach ($targets as $target) {
    echo " - Found Target: [{$target->id}] {$target->name} (Type: " . ($target->type ?? 'NULL') . ")" . PHP_EOL;
}


