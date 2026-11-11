<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CenterSequence;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoicingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // NOTE: the observer is registered by the application itself in
        // AppServiceProvider::boot(). It used to be registered here, which
        // meant this test proved the guard works only when a test
        // remembered to switch it on — and stayed green while production
        // had no guard at all. Do not re-register it here.
    }

    /** @test */
    public function test_immutability_of_issued_invoice()
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'name' => 'Cust1', 'phone' => '1234567890']);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'status' => 'valid', // Issued
            'issue_date' => now(),
            'supply_date' => now(),
        ]);

        // 1. Try to Update
        //
        // The refusal is a ValidationException so that a JSON request
        // gets a 422 with an errors bag and an Inertia request gets the
        // usual validation errors in the session. The error is keyed by
        // the field that was protected.
        try {
            $invoice->update(['total_tax' => 999]);
            $this->fail('Should not allow update on valid invoice');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('total_tax', $e->errors());
            $this->assertStringContainsString(
                'total_tax',
                $e->errors()['total_tax'][0],
                'The refusal must name the protected field.'
            );
        }

        // 2. Try to Delete
        try {
            $invoice->delete();
            $this->fail('Should not allow delete on valid invoice');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice', $e->errors());
        }
    }

    /** @test */
    public function test_sequential_numbering_with_locking()
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);

        // Use service
        $svc = new InvoiceService(new TaxCalculator);

        $seq1 = CenterSequence::getNextValue($tenant->id, $center->id, 'invoice', 2025);
        $seq2 = CenterSequence::getNextValue($tenant->id, $center->id, 'invoice', 2025);

        $this->assertEquals(1, $seq1);
        $this->assertEquals(2, $seq2);
    }
}
