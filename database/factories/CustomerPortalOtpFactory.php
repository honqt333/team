<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<CustomerPortalOtp>
 */
class CustomerPortalOtpFactory extends Factory
{
    protected $model = CustomerPortalOtp::class;

    public function definition(): array
    {
        // Build a (tenant, customer) pair and let the
        // attributes fall back to sane defaults.
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => '+966501234567',
            'whatsapp' => '+966501234567',
        ]);

        return [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501234567',
            'phone_hash' => hash('sha256', '+966501234567'),
            'code_hash' => Hash::make('000000'),
            'attempts' => 0,
            'max_attempts' => CustomerPortalOtp::DEFAULT_MAX_ATTEMPTS,
            'expires_at' => Carbon::now()->addMinutes(CustomerPortalOtp::TTL_MINUTES),
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_sent_at' => Carbon::now(),
        ];
    }

    public function consumed(): static
    {
        return $this->state(fn () => [
            'consumed_at' => Carbon::now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => Carbon::now()->subMinutes(5),
        ]);
    }
}
