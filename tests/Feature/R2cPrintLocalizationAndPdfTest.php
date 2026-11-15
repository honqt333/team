<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Services\Invoice\SubscriptionInvoiceService;
use App\Services\Print\PrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use stdClass;
use Tests\TestCase;

class R2cPrintLocalizationAndPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_invoice_pdf_generates_in_ar_and_en_with_actual_monthly_and_yearly_cycles(): void
    {
        Storage::fake('local');
        PaymentSettings::create([
            'company_name' => 'خدمة برو',
            'company_name_en' => 'Khidmah Pro',
            'cr_number' => '1010123456',
            'vat_number' => '310000000000003',
            'subscription_vat_rate' => 15,
        ]);

        $tenant = Tenant::create([
            'name' => 'ورشة المدار',
            'slug' => 'madar-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-'.Str::lower(Str::random(5)),
            'monthly_price' => 200,
            'yearly_price' => 2000,
            'is_active' => true,
        ]);

        $monthlySub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $monthlyInvoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $monthlySub->id,
            'invoice_number' => 'SUB-MON-001',
            'subtotal' => 200,
            'discount' => 0,
            'vat' => 30,
            'total' => 230,
            'currency' => 'SAR',
            'status' => 'paid',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
            'paid_at' => now(),
        ]);

        $yearlySub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'yearly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $yearlyInvoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $yearlySub->id,
            'invoice_number' => 'SUB-YR-001',
            'subtotal' => 2000,
            'discount' => 0,
            'vat' => 300,
            'total' => 2300,
            'currency' => 'SAR',
            'status' => 'paid',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
            'paid_at' => now(),
        ]);

        $service = app(SubscriptionInvoiceService::class);

        // Arabic Monthly
        app()->setLocale('ar');
        $arHtml = view('pdf.subscription-invoice', [
            'invoice' => $monthlyInvoice,
            'tenant' => $tenant,
            'subscription' => $monthlySub,
            'plan' => $plan,
            'company' => ['name' => 'خدمة برو', 'name_en' => 'Khidmah Pro'],
            'vat_rate' => 15,
        ])->render();
        $this->assertStringContainsString('اشتراك شهري', $arHtml);
        $this->assertStringNotContainsString('اشتراك سنوي', $arHtml);

        $arPath = $service->generatePdf($monthlyInvoice);
        Storage::disk('local')->assertExists($arPath);
        $this->assertStringStartsWith('%PDF-', (string) Storage::disk('local')->get($arPath));

        // English Monthly & Yearly
        app()->setLocale('en');
        $enMonthlyHtml = view('pdf.subscription-invoice', [
            'invoice' => $monthlyInvoice,
            'tenant' => $tenant,
            'subscription' => $monthlySub,
            'plan' => $plan,
            'company' => ['name' => 'خدمة برو', 'name_en' => 'Khidmah Pro'],
            'vat_rate' => 15,
        ])->render();
        $this->assertStringContainsString('Monthly Subscription', $enMonthlyHtml);
        $this->assertStringContainsString('Subscription Invoice', $enMonthlyHtml);

        $enYearlyHtml = view('pdf.subscription-invoice', [
            'invoice' => $yearlyInvoice,
            'tenant' => $tenant,
            'subscription' => $yearlySub,
            'plan' => $plan,
            'company' => ['name' => 'خدمة برو', 'name_en' => 'Khidmah Pro'],
            'vat_rate' => 15,
        ])->render();
        $this->assertStringContainsString('Annual Subscription', $enYearlyHtml);

        $enPath = $service->generatePdf($yearlyInvoice);
        Storage::disk('local')->assertExists($enPath);
        $this->assertStringStartsWith('%PDF-', (string) Storage::disk('local')->get($enPath));
    }

    public function test_print_service_throws_clear_exception_for_missing_pdf_view(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(PrintService::class)->generate('proforma_invoice', new stdClass);
    }
}
