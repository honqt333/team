<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\PaymentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Probe2Test extends TestCase
{
    use RefreshDatabase;

    public function test_a(): void
    {
        echo "\n[A] count=".PaymentSettings::count().PHP_EOL;
        PaymentSettings::create(['company_legal_name_en' => 'A']);
        echo '[A after create] count='.PaymentSettings::count().PHP_EOL;
        echo '[A] first: '.(PaymentSettings::first()?->company_legal_name_en ?? 'null').PHP_EOL;
    }

    public function test_b(): void
    {
        echo "\n[B] count=".PaymentSettings::count().PHP_EOL;
        PaymentSettings::create(['company_legal_name_en' => 'B']);
        echo '[B after create] count='.PaymentSettings::count().PHP_EOL;
        echo '[B] first: '.(PaymentSettings::first()?->company_legal_name_en ?? 'null').PHP_EOL;
    }
}
