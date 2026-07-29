<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Support\TenantWebsite\ThemeRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantWebsite>
 */
class TenantWebsiteFactory extends Factory
{
    protected $model = TenantWebsite::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'site_name' => fake()->company().' '.trans_choice('موقع|Website', 1, [], 'ar'),
            'tagline' => [
                'ar' => 'مركز متخصص في خدمات السيارات',
                'en' => 'Specialists in automotive services',
            ][array_rand(['ar' => 0, 'en' => 1])] ?? '',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#0ea5e9',
            'status' => TenantWebsite::STATUS_DRAFT,
            'theme_key' => ThemeRegistry::DEFAULT_THEME,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => TenantWebsite::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => [
            'status' => TenantWebsite::STATUS_UNPUBLISHED,
        ]);
    }

    public function withContact(): static
    {
        return $this->state(fn () => [
            'contact_phone' => '0501234567',
            'contact_whatsapp' => '0501234567',
            'contact_email' => fake()->companyEmail(),
            'address_line' => 'Riyadh, Saudi Arabia',
        ]);
    }
}
