<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quote;

$quote = Quote::with([
    'lines.service.department',
    'parts.part',
    'parts.unit',
    'customer',
    'vehicle',
    'departments',
])->first();

if (!$quote) {
    echo "No quotes found.\n";
    exit;
}

echo "Quote ID: " . $quote->id . "\n";
echo "Departments count: " . $quote->departments->count() . "\n";
echo "Departments serialized type: " . gettype(json_decode(json_encode($quote->departments))) . "\n";
echo "Departments keys: " . json_encode($quote->departments->keys()) . "\n";
