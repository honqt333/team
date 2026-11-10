<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Backup\TenantBackupService;
use App\Services\Invoice\SubscriptionInvoiceService;
use App\Support\Permissions;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Mockery;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FirstBatchSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id, 'is_active' => true]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        return $user;
    }

    private function invitation(User $user, string $token = 'test-secret'): string
    {
        $user->forceFill(['invitation_token_hash' => hash('sha256', $token), 'invitation_expires_at' => now()->addHour()])->save();

        return URL::temporarySignedRoute('invitations.accept', now()->addHour(), ['user' => $user->id, 'token' => $token]);
    }

    public function test_invitation_is_consumed_once_and_old_signed_links_are_rejected(): void
    {
        $user = $this->actor();
        $url = $this->invitation($user);
        $this->post($url, ['password' => 'ValidPass123!', 'password_confirmation' => 'ValidPass123!'])->assertRedirect();
        $this->assertNull($user->fresh()->invitation_token_hash);
        auth()->logout();
        $this->post($url, ['password' => 'OtherPass123!', 'password_confirmation' => 'OtherPass123!'])->assertForbidden();
        $this->assertTrue(Hash::check('ValidPass123!', $user->fresh()->password));
        $this->get(URL::temporarySignedRoute('invitations.accept', now()->addHour(), ['user' => $user->id]))->assertForbidden();
    }

    public function test_invitation_cannot_reactivate_disabled_account(): void
    {
        $user = $this->actor();
        $url = $this->invitation($user);
        $user->update(['is_active' => false]);
        $this->post($url, ['password' => 'ValidPass123!', 'password_confirmation' => 'ValidPass123!'])->assertForbidden();
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_invitation_requires_two_factor_before_authenticating(): void
    {
        $user = $this->actor();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $url = $this->invitation($user);
        $this->post($url, ['password' => 'ValidPass123!', 'password_confirmation' => 'ValidPass123!'])->assertRedirect(route('app.2fa.challenge'))->assertSessionHas('2fa:user_id', $user->id);
        $this->assertGuest();
    }

    public function test_user_manager_cannot_assign_owner_or_foreign_role_or_foreign_center(): void
    {
        $user = $this->actor();
        $user->givePermissionTo(Permissions::USERS_CREATE);
        $owner = Role::where('tenant_id', $user->tenant_id)->where('name', 'super_admin')->firstOrFail();
        $data = ['name' => 'New User', 'email' => 'new@example.test', 'password' => 'ValidPass123!', 'password_confirmation' => 'ValidPass123!', 'centers' => [$user->current_center_id], 'role_id' => $owner->id];
        $this->actingAs($user)->post(route('settings.users.store'), $data)->assertSessionHasErrors('role_id');
        $foreign = Center::factory()->create();
        $data['role_id'] = null;
        $data['centers'] = [$foreign->id];
        $this->post(route('settings.users.store'), $data)->assertSessionHasErrors('centers.0');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_user_manager_cannot_reset_owner_password(): void
    {
        $actor = $this->actor();
        $actor->givePermissionTo(Permissions::USERS_UPDATE);
        $owner = User::factory()->create(['tenant_id' => $actor->tenant_id, 'current_center_id' => $actor->current_center_id]);
        $owner->assignRole('super_admin');
        $this->actingAs($actor)->put(route('settings.users.update', $owner), ['name' => $owner->name, 'email' => $owner->email, 'centers' => [$actor->current_center_id], 'password' => 'Changed123!', 'password_confirmation' => 'Changed123!'])->assertForbidden();
    }

    public function test_role_manager_cannot_create_reserved_role_or_grant_unheld_permission(): void
    {
        $actor = $this->actor();
        $actor->givePermissionTo(Permissions::USERS_CREATE);
        $this->actingAs($actor)->post('/app/settings/roles', ['name' => 'elevated', 'permissions' => [Permissions::INVOICES_CREATE]])->assertSessionHasErrors('permissions.0');
        $this->post('/app/settings/roles', ['name' => 'super_admin'])->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('roles', ['tenant_id' => $actor->tenant_id, 'name' => 'elevated']);
    }

    public function test_backup_false_write_fails_and_blocks_tenant_deletion(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = AdminUser::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => Hash::make('password'), 'role' => 'super_admin', 'is_active' => true]);
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($admin, 'admin')->delete(route('system.tenants.destroy', $tenant), ['confirmation' => $tenant->name])->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('tenant_backups', ['tenant_id' => $tenant->id, 'status' => 'failed']);
    }

    public function test_backup_success_is_verified_against_saved_bytes(): void
    {
        Storage::fake('local');
        $tenant = Tenant::factory()->create();
        $backup = app(TenantBackupService::class)->createBackup($tenant, 'Verified');
        $this->assertSame('completed', $backup->status);
        $this->assertSame($backup->checksum, hash('sha256', Storage::disk('local')->get($backup->path)));
    }

    public function test_legacy_document_migration_is_dry_run_and_verifies_before_removing(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'invoices/subscription/INV-TEST.pdf';
        Storage::disk('public')->put($path, 'sensitive');
        Storage::disk('public')->put('bank-transfer-receipts/1/receipt.png', 'receipt bytes');
        $this->artisan('billing:privatize-documents')->assertSuccessful();
        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
        $this->artisan('billing:privatize-documents', ['--apply' => true])->assertSuccessful();
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing('bank-transfer-receipts/1/receipt.png');
        $this->assertSame('receipt bytes', Storage::disk('local')->get('bank-transfer-receipts/1/receipt.png'));
        $this->assertSame('sensitive', Storage::disk('local')->get($path));
        Storage::disk('public')->put($path, 'new');
        $this->artisan('billing:privatize-documents', ['--apply' => true])->assertFailed();
        $this->assertSame('new', Storage::disk('public')->get($path));
    }

    public function test_user_manager_cannot_take_over_custom_privileged_account(): void
    {
        $actor = $this->actor();
        $actor->givePermissionTo(Permissions::USERS_UPDATE);
        $target = User::factory()->create(['tenant_id' => $actor->tenant_id, 'current_center_id' => $actor->current_center_id]);
        $target->givePermissionTo(Permissions::INVOICES_CREATE);
        $this->actingAs($actor)->put(route('settings.users.update', $target), ['name' => $target->name, 'email' => $target->email, 'centers' => [$actor->current_center_id], 'password' => 'Changed123!', 'password_confirmation' => 'Changed123!'])->assertForbidden();
    }

    public function test_receipt_download_requires_permission_and_tenant_ownership(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $actor = $this->actor();
        $actor->givePermissionTo(Permissions::COMPANY_MANAGE);
        $receipt = BankTransferReceipt::create(['tenant_id' => $actor->tenant_id, 'receipt_path' => 'bank-transfer-receipts/test.pdf', 'transfer_amount' => 100, 'transfer_date' => now(), 'status' => 'pending']);
        Storage::disk('local')->put($receipt->receipt_path, 'private receipt');
        $url = route('app.billing.receipts.download', $receipt);
        $this->get($url)->assertRedirect(route('login'));
        $response = $this->actingAs($actor)->get($url)->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        Storage::disk('public')->assertMissing($receipt->receipt_path);
        $outsider = $this->actor();
        $outsider->givePermissionTo(Permissions::COMPANY_MANAGE);
        $this->actingAs($outsider)->get($url)->assertNotFound();
    }

    public function test_expired_or_rotated_invitation_is_rejected(): void
    {
        $user = $this->actor();
        $first = $this->invitation($user, 'first');
        $second = $this->invitation($user, 'second');
        $this->get($first)->assertForbidden();
        $user->forceFill(['invitation_expires_at' => now()->subMinute()])->save();
        $this->get($second)->assertForbidden();
    }

    public function test_private_invoice_url_uses_authorized_route_and_preserves_download(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $actor = $this->actor();
        $plan = Plan::create(['name' => 'Test', 'name_ar' => 'Test', 'name_en' => 'Test', 'slug' => 'private-doc-test', 'price_monthly' => 100]);
        $subscription = Subscription::create(['tenant_id' => $actor->tenant_id, 'plan_id' => $plan->id, 'status' => 'active']);
        $invoice = SubscriptionInvoice::create(['tenant_id' => $actor->tenant_id, 'subscription_id' => $subscription->id, 'invoice_number' => 'INV-PRIVATE', 'subtotal' => 100, 'vat' => 15, 'total' => 115, 'status' => 'pending', 'issued_at' => now()]);
        $path = 'invoices/subscription/INV-PRIVATE.pdf';
        Storage::disk('local')->put($path, '%PDF-private');
        $service = app(SubscriptionInvoiceService::class);
        $url = $service->getPdfUrl($invoice);
        $this->assertStringNotContainsString('/storage/', $url);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($actor)->get($url)->assertForbidden();
        $admin = AdminUser::create(['name' => 'Admin', 'email' => 'document-admin@example.test', 'password' => Hash::make('password'), 'role' => 'super_admin', 'is_active' => true]);
        auth()->guard('web')->logout();
        $this->actingAs($admin, 'admin')->get($url)->assertOk();
        Storage::disk('public')->assertMissing($path);
    }
}
