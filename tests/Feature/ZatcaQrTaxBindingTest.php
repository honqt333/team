<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZatcaQrTaxBindingTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantSetup(bool $vatEnabled): array
    {
        $tenant = Tenant::create([
            'name' => 'مركز اختبار الضريبة',
            'legal_name' => 'شركة اختبار الضريبة المحدودة',
            'slug' => 'tax-qr-test-'.uniqid(),
            'status' => 'active',
            'vat_number' => $vatEnabled ? '300000000000003' : null,
        ]);

        TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => $vatEnabled,
            'services_vat_rate' => 15,
            'parts_vat_rate' => 15,
            'tax_number' => $vatEnabled ? '300000000000003' : null,
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'الفرع الرئيسي',
            'name_ar' => 'الفرع الرئيسي',
            'slug' => 'main-'.uniqid(),
            'is_active' => true,
            'is_main' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل تجريبي',
            'phone' => '0500000001',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        return [$tenant, $center, $customer, $user];
    }

    public function test_issue_invoice_generates_zatca_qr_only_when_tax_is_enabled(): void
    {
        [$tenantTax, $centerTax, $customerTax] = $this->createTenantSetup(true);

        $taxInvoice = Invoice::create([
            'tenant_id' => $tenantTax->id,
            'center_id' => $centerTax->id,
            'customer_id' => $customerTax->id,
            'invoice_number' => 'DRAFT-TAX-1',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $invoiceService = app(InvoiceService::class);
        $issuedTax = $invoiceService->issueInvoice($taxInvoice);

        $this->assertSame('valid', $issuedTax->status);
        $this->assertNotNull($issuedTax->zatca_qr_tlv);
        $decoded = base64_decode((string) $issuedTax->zatca_qr_tlv, true);
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('300000000000003', $decoded);

        // Non-tax invoice must NEVER generate QR TLV
        [$tenantNoTax, $centerNoTax, $customerNoTax] = $this->createTenantSetup(false);

        $nonTaxInvoice = Invoice::create([
            'tenant_id' => $tenantNoTax->id,
            'center_id' => $centerNoTax->id,
            'customer_id' => $customerNoTax->id,
            'invoice_number' => 'DRAFT-NOTAX-1',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'total_excl_tax' => 100.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 100.00,
        ]);

        $issuedNonTax = $invoiceService->issueInvoice($nonTaxInvoice);

        $this->assertSame('valid', $issuedNonTax->status);
        $this->assertNull($issuedNonTax->zatca_qr_tlv);
    }
}
