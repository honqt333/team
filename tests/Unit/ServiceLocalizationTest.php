<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Service;
use Tests\TestCase;

class ServiceLocalizationTest extends TestCase
{
    public function test_service_name_attribute_never_returns_null_when_names_are_null(): void
    {
        $service = new Service;
        $service->name_ar = null;
        $service->name_en = null;

        $this->assertSame('', $service->name);
    }

    public function test_service_name_falls_back_correctly(): void
    {
        $service = new Service;
        $service->name_ar = 'صيانة دورية';
        $service->name_en = null;

        app()->setLocale('en');
        $this->assertSame('صيانة دورية', $service->name);

        app()->setLocale('ar');
        $this->assertSame('صيانة دورية', $service->name);

        $service->name_en = 'Periodic Maintenance';
        app()->setLocale('en');
        $this->assertSame('Periodic Maintenance', $service->name);
    }
}
