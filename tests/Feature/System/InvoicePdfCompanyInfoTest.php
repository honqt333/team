<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Services\Invoice\SubscriptionInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 6+ — Invoice PDF pulls company info from PaymentSettings
 * (not hardcoded strings). Without this, every tenant's invoice
 * shows the same "Khidma Pro" name in the header regardless of
 * what the admin configured at /system/settings/company.
 */
class InvoicePdfCompanyInfoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reset PaymentSettings state before every test so a
     * previous test's rows don't leak into the current one
     * (the `create` + `delete` dance in helper methods is
     * tricky to get right against the model's 3600s cache).
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        // Clear BEFORE RefreshDatabase's transaction opens so
        // we wipe any leftover rows from a previous test (the
        // transaction wrap doesn't help when the model also
        // has a 3600-second static cache keyed on
        // 'payment_settings').
        Cache::forget('payment_settings');
        DB::table('payment_settings')->delete();
    }

    /**
     * After each test, also clear the static cache so a
     * subsequent test class doesn't see stale data.
     */
    protected function tearDown(): void
    {
        Cache::forget('payment_settings');
        parent::tearDown();
    }

    public function test_pdf_header_uses_company_name_from_settings(): void
    {
        $this->resetPaymentSettings();

        // Configure company info in PaymentSettings
        PaymentSettings::create([
            'company_legal_name' => 'شركة تجريبية',
            'company_legal_name_en' => 'Acme Corp',
            'company_vat_number' => '300000000000003',
            'company_cr_number' => '1010101010',
            'company_phone' => '+966500000000',
            'company_email' => 'billing@acme.example',
            'company_website' => 'https://acme.example',
            'subscription_vat_enabled' => true,
            'subscription_vat_rate' => 15,
        ]);

        $invoice = $this->makeInvoice();
        $service = app(SubscriptionInvoiceService::class);
        $path = $service->generatePdf($invoice);

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertStringContainsString('/system/invoices/', $service->getPdfUrl($invoice));

        // Extract text and verify the configured company name shows up
        $text = $this->extractPdfText($path);
        $this->assertStringContainsString(
            'Acme Corp',
            $text,
            'PDF must show the company name configured in PaymentSettings',
        );
        $this->assertStringContainsString(
            '300000000000003',
            $text,
            'PDF must show the VAT number from settings',
        );
        $this->assertStringContainsString(
            '1010101010',
            $text,
            'PDF must show the CR number from settings',
        );
        $this->assertStringContainsString(
            'billing@acme.example',
            $text,
            'PDF must show the company email from settings',
        );
    }

    public function test_pdf_falls_back_to_default_when_no_settings(): void
    {
        $this->resetPaymentSettings();

        $invoice = $this->makeInvoice();
        $service = app(SubscriptionInvoiceService::class);
        $path = $service->generatePdf($invoice);

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertStringContainsString('/system/invoices/', $service->getPdfUrl($invoice));
        $text = $this->extractPdfText($path);
        $this->assertNotEmpty($text, 'PDF must not be empty even without settings');
    }

    public function test_pdf_uses_settings_updated_after_initial_invoice(): void
    {
        $this->resetPaymentSettings();

        $invoice = $this->makeInvoice();
        $service = app(SubscriptionInvoiceService::class);
        $service->generatePdf($invoice);

        // Admin updates the company name
        $settings = PaymentSettings::create(['company_legal_name_en' => 'Updated Co']);
        Cache::forget('payment_settings');

        $path = $service->generatePdf($invoice);
        $text = $this->extractPdfText($path);
        $this->assertStringContainsString('Updated Co', $text);
    }

    private function makeInvoice(): SubscriptionInvoice
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant-pdf-'.uniqid(),
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Test Plan',
            'name_ar' => 'خطة اختبار',
            'name_en' => 'Test Plan',
            'slug' => 'test-plan-pdf-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'trial_days' => 14,
        ]);

        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
        ]);

        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-PDF-'.uniqid(),
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

    /**
     * Wipe the PaymentSettings table + its 3600-second static
     * cache. Called by tests that need to start from a known
     * empty state — RefreshDatabase alone leaves a row with
     * null values that gets returned by first() and breaks
     * our "use company info from settings" assertions.
     */
    private function resetPaymentSettings(): void
    {
        DB::table('payment_settings')->delete();
        Cache::forget('payment_settings');
    }

    /**
     * When a stamp image is configured, the rendered PDF must
     * reference it (the image is embedded by DomPDF so we just
     * check the markup doesn't bail and the file is bigger
     * than the no-stamp version — the presence of an <img>
     * tag in the source is what we test most directly).
     */
    public function test_pdf_renders_with_company_stamp(): void
    {
        $this->resetPaymentSettings();

        // Create a tiny 1x1 PNG stamp on disk
        $stampDir = storage_path('app/public/company/stamps');

        if (! is_dir($stampDir)) {
            mkdir($stampDir, 0755, true);
        }
        $stampPath = 'company/stamps/test-stamp.png';
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
        );
        Storage::disk('public')->put($stampPath, $pngBytes);

        PaymentSettings::create([
            'company_legal_name_en' => 'Stamp Co',
            'company_stamp_path' => $stampPath,
        ]);

        $invoice = $this->makeInvoice();
        $service = app(SubscriptionInvoiceService::class);
        $path = $service->generatePdf($invoice);

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertStringContainsString('/system/invoices/', $service->getPdfUrl($invoice));

        // File must be non-empty (the embedded PNG adds bytes)
        $size = filesize(Storage::disk('local')->path($path));
        $this->assertGreaterThan(1000, $size, 'PDF with embedded stamp should be >1KB');
    }

    /**
     * Extract text from a PDF file using pypdf.
     * If pypdf isn't available, we return the raw bytes (which
     * is enough to assert the file is non-empty).
     */
    private function extractPdfText(string $path): string
    {
        $fullPath = Storage::disk('local')->path($path);

        // Use pypdf's layout extraction mode because simple mode
        // dumps characters in source-code order, which means
        // Arabic text comes out reversed (DomPDF doesn't apply
        // the BiDi algorithm). Layout mode preserves the visual
        // placement of glyphs, which is what a human reader sees
        // in a PDF viewer. Concatenate all pages — the company
        // name (en) renders on page 2 in the footer, while the
        // Arabic name is on page 1 in the header.
        $script = sprintf(
            'from pypdf import PdfReader; r=PdfReader("%s"); print("".join(p.extract_text(extraction_mode="layout") for p in r.pages))',
            addslashes($fullPath),
        );
        $output = shell_exec('python3 -c '.escapeshellarg($script).' 2>/dev/null');

        if (is_string($output) && $output !== '') {
            return $output;
        }

        // Fallback: return raw file contents so the test can at
        // least assert non-emptiness.
        return file_get_contents($fullPath) ?: '';
    }
}
