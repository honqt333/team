<?php

declare(strict_types=1);
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

echo "Adding image_path to parts...\n";

try {
    Schema::table('parts', function (Blueprint $table) {
        if (! Schema::hasColumn('parts', 'image_path')) {
            $table->string('image_path')->nullable()->after('description');
            echo "Column added successfully!\n";
        } else {
            echo "Column already exists.\n";
        }
    });
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
