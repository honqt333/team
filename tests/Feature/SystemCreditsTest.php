<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Credits\SmsPurchase;
use App\Models\Credits\TenantSmsBalance;
use App\Models\Credits\TenantWhatsappBalance;
use App\Models\Credits\WhatsappPurchase;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenancyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemCreditsTest extends TestCase
{
    use RefreshDatabase;

    private function systemAdmin(string $role = 'super_admin'): AdminUser
    {
        return AdminUser::create([
            'name' => 'System Admin',
            'email' => 'sysadmin-'.uniqid().'@example.test',
            'password' => Hash::make('secret-password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_get_or_create_creates_and_returns_existing_balances_with_tenant_isolation(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);

        // 1. Creates new balances when missing
        $smsA = TenantSmsBalance::getOrCreate($tenantA->id);
        $waA = TenantWhatsappBalance::getOrCreate($tenantA->id);

        $this->assertSame($tenantA->id, $smsA->tenant_id);
        $this->assertSame(0, (int) $smsA->balance);
        $this->assertSame($tenantA->id, $waA->tenant_id);
        $this->assertSame(0, (int) $waA->balance);

        // 2. Returns existing balance without resetting or duplicating
        $smsA->addCredits(50);
        $waA->addCredits(25);

        $smsAAgain = TenantSmsBalance::getOrCreate($tenantA->id);
        $waAAgain = TenantWhatsappBalance::getOrCreate($tenantA->id);

        $this->assertSame($smsA->id, $smsAAgain->id);
        $this->assertSame(50, (int) $smsAAgain->balance);
        $this->assertSame($waA->id, $waAAgain->id);
        $this->assertSame(25, (int) $waAAgain->balance);
        $this->assertSame(1, TenantSmsBalance::where('tenant_id', $tenantA->id)->count());
        $this->assertSame(1, TenantWhatsappBalance::where('tenant_id', $tenantA->id)->count());

        // 3. Cross-tenant isolation via TenantScoped
        $smsB = TenantSmsBalance::getOrCreate($tenantB->id);
        $smsB->addCredits(10);

        TenancyContext::runForTenant($tenantA->id, null, function () use ($tenantA, $tenantB) {
            $visibleSms = TenantSmsBalance::query()->get();
            $this->assertCount(1, $visibleSms);
            $this->assertSame($tenantA->id, $visibleSms->first()->tenant_id);
            $this->assertNull(TenantSmsBalance::query()->where('tenant_id', $tenantB->id)->first());
        });
    }

    public function test_authorized_system_admin_can_add_sms_and_whatsapp_credits(): void
    {
        $admin = $this->systemAdmin('super_admin');
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);

        TenantSmsBalance::getOrCreate($tenantB->id)->addCredits(15);
        TenantWhatsappBalance::getOrCreate($tenantB->id)->addCredits(20);

        // Add SMS credits (pass string tenant_id & credits to verify strict_types safety)
        $smsResponse = $this->actingAs($admin, 'admin')->post(route('system.sms.add-credits'), [
            'tenant_id' => (string) $tenantA->id,
            'credits' => '100',
            'reason' => 'شحن تجريبي',
        ]);
        $smsResponse->assertRedirect();

        // Add WhatsApp credits
        $waResponse = $this->actingAs($admin, 'admin')->post(route('system.whatsapp.add-credits'), [
            'tenant_id' => (string) $tenantA->id,
            'credits' => '40',
            'reason' => 'شحن واتساب',
        ]);
        $waResponse->assertRedirect();

        $this->assertSame(100, (int) TenantSmsBalance::where('tenant_id', $tenantA->id)->value('balance'));
        $this->assertSame(40, (int) TenantWhatsappBalance::where('tenant_id', $tenantA->id)->value('balance'));
        $this->assertSame(1, SmsPurchase::where('tenant_id', $tenantA->id)->where('payment_gateway', 'manual')->count());
        $this->assertSame(1, WhatsappPurchase::where('tenant_id', $tenantA->id)->where('payment_gateway', 'manual')->count());

        // Tenant B balances remain untouched
        $this->assertSame(15, (int) TenantSmsBalance::where('tenant_id', $tenantB->id)->value('balance'));
        $this->assertSame(20, (int) TenantWhatsappBalance::where('tenant_id', $tenantB->id)->value('balance'));
    }

    public function test_unauthorized_users_cannot_add_or_modify_credits(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        TenantSmsBalance::getOrCreate($tenant->id)->addCredits(30);
        TenantWhatsappBalance::getOrCreate($tenant->id)->addCredits(12);

        // 1. Guest redirected
        $this->post(route('system.sms.add-credits'), [
            'tenant_id' => $tenant->id,
            'credits' => 50,
        ])->assertRedirect();

        // 2. Regular tenant user cannot access system credits routes
        $tenantUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_system_admin' => false,
        ]);
        $this->actingAs($tenantUser)
            ->post(route('system.sms.add-credits'), [
                'tenant_id' => $tenant->id,
                'credits' => 50,
            ])
            ->assertForbidden();

        auth()->guard('web')->logout();

        // 3. Support AdminUser without system.credits.manage is rejected (403)
        $supportAdmin = $this->systemAdmin('support');
        $this->actingAs($supportAdmin, 'admin')
            ->post(route('system.sms.add-credits'), [
                'tenant_id' => $tenant->id,
                'credits' => 50,
            ])
            ->assertForbidden();

        $this->actingAs($supportAdmin, 'admin')
            ->post(route('system.whatsapp.add-credits'), [
                'tenant_id' => $tenant->id,
                'credits' => 50,
            ])
            ->assertForbidden();

        // Balances must remain completely unchanged
        $this->assertSame(30, (int) TenantSmsBalance::where('tenant_id', $tenant->id)->value('balance'));
        $this->assertSame(12, (int) TenantWhatsappBalance::where('tenant_id', $tenant->id)->value('balance'));
    }

    public function test_add_credits_validates_tenant_and_rejects_invalid_or_excessive_amounts(): void
    {
        $admin = $this->systemAdmin('super_admin');
        $tenant = Tenant::factory()->create(['status' => 'active']);
        TenantSmsBalance::getOrCreate($tenant->id)->addCredits(20);
        TenantWhatsappBalance::getOrCreate($tenant->id)->addCredits(10);

        // 1. Non-existent tenant_id
        $this->actingAs($admin, 'admin')
            ->post(route('system.sms.add-credits'), [
                'tenant_id' => 999999,
                'credits' => 10,
            ])
            ->assertSessionHasErrors('tenant_id');

        // 2. Fractional, negative, zero, and excessive credits
        foreach ([12.5, '12.5', -5, 0, 9999999999] as $invalidCredits) {
            $this->actingAs($admin, 'admin')
                ->post(route('system.sms.add-credits'), [
                    'tenant_id' => $tenant->id,
                    'credits' => $invalidCredits,
                ])
                ->assertSessionHasErrors('credits');

            $this->actingAs($admin, 'admin')
                ->post(route('system.whatsapp.add-credits'), [
                    'tenant_id' => $tenant->id,
                    'credits' => $invalidCredits,
                ])
                ->assertSessionHasErrors('credits');
        }

        // Balances must remain completely unchanged
        $this->assertSame(20, (int) TenantSmsBalance::where('tenant_id', $tenant->id)->value('balance'));
        $this->assertSame(10, (int) TenantWhatsappBalance::where('tenant_id', $tenant->id)->value('balance'));
    }
}
