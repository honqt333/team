<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

echo "Starting update...\n";
WorkOrder::where('total_incl_tax', 0)->chunk(100, function($orders) {
    foreach($orders as $order) {
        $order->recalculateTotals();
        $order->saveQuietly();
        echo ".";
    }
    echo "\nUpdated chunk.\n";
});
echo "Done.\n";
