<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

// login first user
$user = User::first();
if (!$user) { echo "No users found!\n"; exit; }
Auth::login($user);
echo "Logged in as: " . $user->name . " (Center: " . ($user->current_center_id ?? 'NULL') . ")" . PHP_EOL;

// Get a customer (source)
$source = Customer::first();
if (!$source) { echo "No customers found!\n"; exit; }
echo "Source Customer: " . $source->name . " (ID: $source->id)" . PHP_EOL;

// Call Controller Logic Manually to debug Query
echo "--- Testing Query Logic ---" . PHP_EOL;

// LOGIC FROM CONTROLLER:
$query = Customer::withoutGlobalScopes()
    ->where('id', '!=', $source->id)
    ->withCount(['vehicles', 'quotes', 'workOrders'])
    ->orderBy('name');

echo "SQL: " . $query->toSql() . PHP_EOL;
$results = $query->get();

echo "Results Count: " . $results->count() . PHP_EOL;
foreach($results as $r) {
    echo " - " . $r->name . " (ID: $r->id)" . PHP_EOL;
}
