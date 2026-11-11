<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Invoice\SubscriptionInvoiceService;
use App\Services\Print\PrintService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Batch 1 / item 3 — issuer identity on printed documents.
 *
 * Sales invoices belong to the tenant that issued them, so the PDF
 * header must show that tenant's name/VAT/CR. Subscription invoices
 * are issued by the platform, so they show the platform identity.
 * Both freeze the identity at creation and keep it on reprint even
 * after the tenant/platform is renamed.
 */
class InvoiceIssuerIdentityPdfTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $alphaTenant;

    private Tenant $betaTenant;

    private Center $alphaCenter;

    private Center $betaCenter;

    private Invoice $alphaInvoice;

    private Invoice $betaInvoice;

    private SubscriptionInvoice $platformInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        Cache::forget('payment_settings');
        DB::table('payment_settings')->delete();

        $this->alphaTenant = Tenant::factory()->create([
            'name' => 'Alpha Holding',
            'slug' => 'alpha-holding-'.uniqid(),
            'legal_name' => 'شركة ألفا للسيارات',
            'legal_name_en' => 'Alpha Motors LLC',
            'vat_number' => '311111111111113',
            'cr_number' => '1010101010',
            'phone' => '+966500000001',
            'email' => 'billing@alpha.example',
        ]);

        $this->betaTenant = Tenant::factory()->create([
            'name' => 'Beta Holding',
            'slug' => 'beta-holding-'.uniqid(),
            'legal_name' => 'شركة بيتا للورش',
            'legal_name_en' => 'Beta Garage LLC',
            'vat_number' => '322222222222223',
            'cr_number' => '2020202020',
            'phone' => '+966500000002',
            'email' => 'billing@beta.example',
        ]);

        $this->alphaCenter = Center::factory()->create([
            'tenant_id' => $this->alphaTenant->id,
            'name' => 'Alpha Riyadh',
            'name_ar' => 'فرع الرياض',
        ]);

        $this->betaCenter = Center::factory()->create([
            'tenant_id' => $this->betaTenant->id,
            'name' => 'Beta Jeddah',
            'name_ar' => 'فرع جدة',
        ]);

        $this->alphaInvoice = $this->makeTenantInvoice($this->alphaTenant, $this->alphaCenter, 'INV-ALPHA-0001');
        $this->betaInvoice = $this->makeTenantInvoice($this->betaTenant, $this->betaCenter, 'INV-BETA-0001');

        PaymentSettings::create([
            'company_legal_name' => 'منصة خدمة برو',
            'company_legal_name_en' => 'Khidmh Platform FZE',
            'company_vat_number' => '399999999999993',
            'company_cr_number' => '9090909090',
            'company_email' => 'billing@khidmh.example',
            'subscription_vat_enabled' => true,
            'subscription_vat_rate' => 15,
        ]);
        Cache::forget('payment_settings');

        $this->platformInvoice = $this->makeSubscriptionInvoice($this->alphaTenant);
    }

    public function test_each_document_prints_its_own_issuer_identity(): void
    {
        $alphaText = $this->extractPdfText($this->generateTenantPdf($this->alphaInvoice));
        $betaText = $this->extractPdfText($this->generateTenantPdf($this->betaInvoice));
        $platformText = $this->extractPdfText(
            app(SubscriptionInvoiceService::class)->generatePdf($this->platformInvoice),
        );

        // Tenant A sales invoice.
        $this->assertStringContainsString('Alpha Motors LLC', $alphaText);
        $this->assertStringContainsString('311111111111113', $alphaText);
        $this->assertStringContainsString('1010101010', $alphaText);
        $this->assertStringNotContainsString('Beta Garage LLC', $alphaText);
        $this->assertStringNotContainsString('Khidmh Platform FZE', $alphaText);

        // Tenant B sales invoice — same code path, different issuer.
        $this->assertStringContainsString('Beta Garage LLC', $betaText);
        $this->assertStringContainsString('322222222222223', $betaText);
        $this->assertStringContainsString('2020202020', $betaText);
        $this->assertStringNotContainsString('Alpha Motors LLC', $betaText);

        // Platform subscription invoice shows the platform identity.
        $this->assertStringContainsString('Khidmh Platform FZE', $platformText);
        $this->assertStringContainsString('399999999999993', $platformText);
        $this->assertStringNotContainsString('Alpha Motors LLC', $platformText);
    }

    public function test_reprint_keeps_the_identity_frozen_at_issuance(): void
    {
        // First print, while the original identities are in force.
        $this->generateTenantPdf($this->alphaInvoice);
        app(SubscriptionInvoiceService::class)->generatePdf($this->platformInvoice);

        $this->assertSame('Alpha Motors LLC', $this->alphaInvoice->fresh()->seller_name_en_snapshot);
        $this->assertSame('311111111111113', $this->alphaInvoice->fresh()->seller_vat_snapshot);
        $this->assertSame('Khidmh Platform FZE', $this->platformInvoice->fresh()->seller_name_en_snapshot);

        // The tenant and the platform are renamed after issuance.
        $this->alphaTenant->update([
            'legal_name' => 'شركة ألفا الجديدة',
            'legal_name_en' => 'Alpha Motors Renamed LLC',
            'vat_number' => '355555555555553',
            'cr_number' => '3030303030',
        ]);

        PaymentSettings::first()->update([
            'company_legal_name' => 'منصة خدمة برو الجديدة',
            'company_legal_name_en' => 'Renamed Platform FZE',
            'company_vat_number' => '388888888888883',
            'company_cr_number' => '8080808080',
        ]);
        Cache::forget('payment_settings');

        // Reprints must still show the identity from the issuance date.
        $alphaText = $this->extractPdfText($this->generateTenantPdf($this->alphaInvoice->fresh()));
        $platformText = $this->extractPdfText(
            app(SubscriptionInvoiceService::class)->generatePdf($this->platformInvoice->fresh()),
        );

        $this->assertStringContainsString('Alpha Motors LLC', $alphaText);
        $this->assertStringContainsString('311111111111113', $alphaText);
        $this->assertStringContainsString('1010101010', $alphaText);
        $this->assertStringNotContainsString('Alpha Motors Renamed LLC', $alphaText);
        $this->assertStringNotContainsString('355555555555553', $alphaText);

        $this->assertStringContainsString('Khidmh Platform FZE', $platformText);
        $this->assertStringContainsString('399999999999993', $platformText);
        $this->assertStringContainsString('9090909090', $platformText);
        $this->assertStringNotContainsString('Renamed Platform FZE', $platformText);
        $this->assertStringNotContainsString('388888888888883', $platformText);
    }

    public function test_invoice_print_page_carries_the_frozen_seller_identity(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');

        $user = User::factory()->create([
            'tenant_id' => $this->alphaTenant->id,
            'current_center_id' => $this->alphaCenter->id,
        ]);
        $user->centers()->attach($this->alphaCenter->id, ['tenant_id' => $this->alphaTenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->alphaTenant->id);
        $user->givePermissionTo(Permissions::INVOICES_VIEW);

        $this->actingAsWithTeam($user)
            ->get(route('app.invoices.print', $this->alphaInvoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Print')
                ->where('invoice.seller_name_snapshot', 'شركة ألفا للسيارات')
                ->where('invoice.seller_name_en_snapshot', 'Alpha Motors LLC')
                ->where('invoice.seller_vat_snapshot', '311111111111113')
                ->where('invoice.seller_cr_snapshot', '1010101010')
            );

        // Rename the tenant after issuance; the print page must still
        // receive the frozen identity from the invoice row.
        $this->alphaTenant->update([
            'legal_name' => 'شركة ألفا الجديدة',
            'legal_name_en' => 'Alpha Motors Renamed LLC',
            'vat_number' => '355555555555553',
            'cr_number' => '3030303030',
        ]);

        $this->actingAsWithTeam($user)
            ->get(route('app.invoices.print', $this->alphaInvoice->fresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Print')
                ->where('invoice.seller_name_snapshot', 'شركة ألفا للسيارات')
                ->where('invoice.seller_name_en_snapshot', 'Alpha Motors LLC')
                ->where('invoice.seller_vat_snapshot', '311111111111113')
                ->where('invoice.seller_cr_snapshot', '1010101010')
            );
    }

    public function test_legacy_named_issuer_keeps_missing_registration_numbers_on_reprint(): void
    {
        $this->alphaInvoice->update(['seller_vat_snapshot' => null, 'seller_cr_snapshot' => null]);
        $this->alphaTenant->update(['vat_number' => '355555555555553', 'cr_number' => '3030303030']);

        $text = $this->extractPdfText($this->generateTenantPdf($this->alphaInvoice->fresh()));
        $this->assertStringContainsString('Alpha Motors LLC', $text);
        $this->assertStringNotContainsString('355555555555553', $text);
        $this->assertStringNotContainsString('3030303030', $text);

        $this->platformInvoice->update(['seller_vat_snapshot' => null, 'seller_cr_snapshot' => null]);
        $text = $this->extractPdfText(app(SubscriptionInvoiceService::class)->generatePdf($this->platformInvoice->fresh()));
        $this->assertStringContainsString('Khidmh Platform FZE', $text);
        $this->assertStringNotContainsString('399999999999993', $text);
        $this->assertStringNotContainsString('9090909090', $text);
    }

    public function test_subscription_issued_before_platform_setup_keeps_its_original_identity(): void
    {
        DB::table('payment_settings')->delete();
        $invoice = $this->makeSubscriptionInvoice($this->alphaTenant);
        PaymentSettings::create(['company_legal_name_en' => 'Later Platform', 'company_vat_number' => '388888888888883']);
        $this->assertSame('', $invoice->fresh()->seller_vat_snapshot);
        $text = $this->extractPdfText(app(SubscriptionInvoiceService::class)->generatePdf($invoice->fresh()));
        $this->assertStringContainsString(config('app.name'), $text);
        $this->assertStringNotContainsString('Later Platform', $text);
        $this->assertStringNotContainsString('388888888888883', $text);

        $path = app(PrintService::class)->generate('subscription_invoice', $invoice->fresh());
        $genericText = $this->extractPdfText($path);
        $this->assertStringContainsString(config('app.name'), $genericText);
        $this->assertStringNotContainsString('388888888888883', $genericText);
    }

    private function makeTenantInvoice(Tenant $tenant, Center $center, string $number): Invoice
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل '.$number,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => $number,
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'customer_name_snapshot' => $customer->name,
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15,
            'currency_code' => 'SAR',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_taxable_amount' => 100,
            'total_paid' => 115,
        ]);

        $invoice->lines()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'description' => 'خدمة صيانة دورية',
            'qty' => 1,
            'unit_price' => 100,
            'discount_amount' => 0,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15,
            'tax_amount' => 15,
            'line_total_excl_tax' => 100,
            'line_total_incl_tax' => 115,
        ]);

        return $invoice;
    }

    private function makeSubscriptionInvoice(Tenant $tenant): SubscriptionInvoice
    {
        $plan = Plan::create([
            'name' => 'Batch 1 Plan',
            'name_ar' => 'خطة اختبار',
            'name_en' => 'Batch 1 Plan',
            'slug' => 'batch1-plan-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'trial_days' => 14,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
        ]);

        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'SUB-INV-'.uniqid(),
            'subtotal' => 100,
            'discount' => 0,
            'vat' => 15,
            'total' => 115,
            'status' => 'paid',
            'issued_at' => now()->subDay(),
            'due_at' => now()->addDays(7),
            'paid_at' => now(),
        ]);
    }

    private function generateTenantPdf(Invoice $invoice): string
    {
        return app(PrintService::class)->generate('invoice', $invoice);
    }

    private function extractPdfText(string $path): string
    {
        $disk = str_starts_with($path, 'invoices/subscription/') ? 'local' : 'public';
        $fullPath = Storage::disk($disk)->path($path);

        $script = sprintf(
            'from pypdf import PdfReader; r=PdfReader("%s"); print("".join(p.extract_text(extraction_mode="layout") for p in r.pages))',
            addslashes($fullPath),
        );
        $output = shell_exec('python3 -c '.escapeshellarg($script).' 2>/dev/null');

        if (is_string($output) && $output !== '') {
            return $output;
        }

        return file_get_contents($fullPath) ?: '';
    }
}
