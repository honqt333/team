<?php

namespace Tests\Feature;

use App\Models\CenterSequence;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Center;
use App\Models\Customer;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use App\Observers\InvoiceObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class InvoicingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Register observer manually for test if not in ServiceProvider yet
        Invoice::observe(InvoiceObserver::class);
    }

    /** @test */
    public function test_immutability_of_issued_invoice()
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'name' => 'Cust1']);
        
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
        try {
            $invoice->update(['total_tax' => 999]);
            $this->fail("Should not allow update on valid invoice");
        } catch (\Exception $e) {
            $this->assertStringContainsString("Cannot edit invoice", $e->getMessage());
        }

        // 2. Try to Delete
        try {
            $invoice->delete();
            $this->fail("Should not allow delete on valid invoice");
        } catch (\Exception $e) {
            $this->assertStringContainsString("Cannot delete invoice", $e->getMessage());
        }
    }

    /** @test */
    public function test_sequential_numbering_with_locking()
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        
        // Use service
        $svc = new InvoiceService(new TaxCalculator());
        
        $seq1 = CenterSequence::getNextValue($tenant->id, $center->id, 'invoice', 2025);
        $seq2 = CenterSequence::getNextValue($tenant->id, $center->id, 'invoice', 2025);
        
        $this->assertEquals(1, $seq1);
        $this->assertEquals(2, $seq2);
    }
}
