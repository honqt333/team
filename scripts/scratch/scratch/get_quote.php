<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$quote = \App\Models\Quote::latest()->first();
if ($quote) {
    echo "ID: " . $quote->id . "\n";
    echo "Code: " . $quote->code . "\n";
} else {
    echo "No quotes found.\n";
}
