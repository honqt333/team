<?php

declare(strict_types=1);

use App\Models\Quote;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$quote = Quote::latest()->first();

if ($quote) {
    echo 'ID: '.$quote->id."\n";
    echo 'Code: '.$quote->code."\n";
} else {
    echo "No quotes found.\n";
}
