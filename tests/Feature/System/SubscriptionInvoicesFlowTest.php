<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Invoice\SubscriptionInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionInvoicesFlowTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = AdminUser::create([
            'name' => 'Invoice Admin',
            'email' => 'invoice-admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-annual-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 1200,
            'trial_days' => 14,
            'limits' => ['max_users' => 10, 'max_centers' => 2, 'storage_gb' => 10],
            'is_active' => true,
        ]);
    }

    public function test_generate_number_is_globally_unique_under_tenant_context_and_deletions(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'Alpha Garage', 'trade_name' => 'كراج ألفا']);
        $tenantB = Tenant::factory()->create(['name' => 'Beta Garage', 'trade_name' => 'كراج بيتا']);
        $tenantBUser = User::factory()->create(['tenant_id' => $tenantB->id]);

        $subA = $this->makeSubscription($tenantA, 'pending');
        $subB = $this->makeSubscription($tenantB, 'pending');

        $year = date('Y');
        $first = SubscriptionInvoice::create([
            'tenant_id' => $tenantA->id,
            'subscription_id' => $subA->id,
            'invoice_number' => "INV-{$year}-000001",
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        SubscriptionInvoice::create([
            'tenant_id' => $tenantA->id,
            'subscription_id' => $subA->id,
            'invoice_number' => "INV-{$year}-000002",
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        // Delete the first invoice so count() would drop back to 1
        $first->delete();

        // Scope TenantScoped to tenantB via authenticated web user (0 invoices for tenantB)
        $this->actingAs($tenantBUser, 'web');

        $nextNumber = SubscriptionInvoice::generateNumber();
        $this->assertSame("INV-{$year}-000003", $nextNumber);

        $created = SubscriptionInvoice::create([
            'tenant_id' => $tenantB->id,
            'subscription_id' => $subB->id,
            'invoice_number' => $nextNumber,
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->assertSame("INV-{$year}-000003", $created->invoice_number);
    }

    public function test_index_filters_overdue_and_searches_by_tenant_name_and_trade_name(): void
    {
        $tenantOne = Tenant::factory()->create([
            'name' => 'Riyadh Motors',
            'trade_name' => 'محركات الرياض',
        ]);
        $tenantTwo = Tenant::factory()->create([
            'name' => 'Jeddah Auto',
            'trade_name' => 'سيارات جدة',
        ]);

        $subOne = $this->makeSubscription($tenantOne, 'pending');
        $subTwo = $this->makeSubscription($tenantTwo, 'active');

        $overdueInvoice = SubscriptionInvoice::create([
            'tenant_id' => $tenantOne->id,
            'subscription_id' => $subOne->id,
            'invoice_number' => 'INV-2026-000101',
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150,
            'status' => 'pending',
            'issued_at' => now()->subDays(20),
            'due_at' => now()->subDays(5),
        ]);

        $futurePending = SubscriptionInvoice::create([
            'tenant_id' => $tenantOne->id,
            'subscription_id' => $subOne->id,
            'invoice_number' => 'INV-2026-000102',
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $paidInvoice = SubscriptionInvoice::create([
            'tenant_id' => $tenantTwo->id,
            'subscription_id' => $subTwo->id,
            'invoice_number' => 'INV-2026-000103',
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150,
            'status' => 'paid',
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDays(2),
            'paid_at' => now()->subDays(2),
        ]);

        // Status=overdue returns ONLY overdue pending invoice
        $this->actingAs($this->admin, 'admin')
            ->get('/system/invoices?status=overdue')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Invoices/Index')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.id', $overdueInvoice->id)
                ->where('stats.overdue', 1)
            );

        // Search by English tenant.name finds Jeddah Auto even without matching trade_name
        $this->actingAs($this->admin, 'admin')
            ->get('/system/invoices?search=Jeddah')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.id', $paidInvoice->id)
            );

        // Search by Arabic trade_name returns tenantOne's 2 invoices
        $this->actingAs($this->admin, 'admin')
            ->get('/system/invoices?search='.urlencode('محركات الرياض'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('invoices.data', 2)
                ->where('invoices.data.0.id', fn ($id) => in_array($id, [$overdueInvoice->id, $futurePending->id], true))
            );
    }

    public function test_show_loads_payments_and_mark_paid_refreshes_cached_pdf(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Show Test Center', 'trade_name' => 'مركز الاختبار']);
        $sub = $this->makeSubscription($tenant, 'pending');

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-2026-000201',
            'subtotal' => 1200,
            'vat' => 180,
            'discount' => 0,
            'total' => 1380,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        // Pre-generate stale pending PDF
        $service = app(SubscriptionInvoiceService::class);
        $pdfPath = $service->generatePdf($invoice);
        $this->assertTrue(Storage::disk('local')->exists($pdfPath));
        $initialSize = Storage::disk('local')->size($pdfPath);

        $this->actingAs($this->admin, 'admin')
            ->post("/system/invoices/{$invoice->id}/mark-paid", [
                'payment_method' => 'cash',
                'paid_amount' => 1380,
                'reference' => 'CASH-RCPT-77',
                'notes' => 'Paid at main office',
            ])
            ->assertRedirect();

        $invoice->refresh();
        $sub->refresh();

        $this->assertSame('paid', $invoice->status);
        $this->assertSame('active', $sub->status);

        $payment = SubscriptionPayment::where('subscription_invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('paid', $payment->status);
        $this->assertSame('cash', $payment->payment_method);
        $this->assertSame('CASH-RCPT-77', $payment->gateway_ref);

        // Fresh PDF exists on disk (regenerated after markPaid)
        $this->assertTrue(Storage::disk('local')->exists($pdfPath));
        $this->assertGreaterThan(0, $initialSize);

        // Show page returns loaded payments and root-relative versioned pdfUrl
        $this->actingAs($this->admin, 'admin')
            ->get("/system/invoices/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Invoices/Show')
                ->where('invoice.status', 'paid')
                ->has('invoice.payments', 1)
                ->where('invoice.payments.0.gateway_ref', 'CASH-RCPT-77')
                ->where('pdfUrl', fn ($url) => str_starts_with((string) $url, "/system/invoices/{$invoice->id}/download") && str_contains((string) $url, 'inline=1') && str_contains((string) $url, 'v='))
            );

        // Inline download serves application/pdf with inline Content-Disposition
        $inlineResponse = $this->actingAs($this->admin, 'admin')
            ->get("/system/invoices/{$invoice->id}/download?inline=1");
        $inlineResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $inlineResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', (string) $inlineResponse->headers->get('Content-Disposition'));
    }

    public function test_cancel_works_for_web_system_admin_cascades_and_refreshes_pdf(): void
    {
        $tenant = Tenant::factory()->create();
        $webAdmin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        $sub = $this->makeSubscription($tenant, 'pending_payment');

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-2026-000301',
            'subtotal' => 1200,
            'vat' => 180,
            'discount' => 0,
            'total' => 1380,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 1380,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment->id,
            'sender_name' => 'Tester',
            'sender_bank' => 'SNB',
            'transfer_amount' => 1380,
            'transfer_date' => now()->toDateString(),
            'reference_number' => 'BNK-001',
            'receipt_path' => 'receipts/test.png',
            'status' => 'pending',
        ]);

        $this->actingAs($webAdmin, 'web')
            ->post("/system/invoices/{$invoice->id}/cancel")
            ->assertRedirect();

        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame('cancelled', $sub->fresh()->status);
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame('superseded', $receipt->fresh()->status);
        $this->assertNull($receipt->fresh()->reviewed_by, 'Web User id must not be written to AdminUser reviewer column');
        $this->assertStringContainsString((string) $webAdmin->name, (string) $receipt->fresh()->review_notes);
        $this->assertTrue(Storage::disk('local')->exists("invoices/subscription/{$invoice->invoice_number}.pdf"));
    }

    public function test_pdf_renders_safely_when_plan_is_missing(): void
    {
        $tenant = Tenant::factory()->create(['trade_name' => 'ورشة التجربة']);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-2026-000401',
            'subtotal' => 500,
            'vat' => 75,
            'discount' => 0,
            'total' => 575,
            'status' => 'cancelled',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $service = app(SubscriptionInvoiceService::class);
        $path = $service->generatePdf($invoice);

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertGreaterThan(500, Storage::disk('local')->size($path));
    }

    private function makeSubscription(Tenant $tenant, string $status): Subscription
    {
        return Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
            'price' => 1200,
            'currency' => 'SAR',
            'status' => $status,
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);
    }
}
