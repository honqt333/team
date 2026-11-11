<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\FixInvoiceAddressSnapshots;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The sanctioned maintenance path for invoice address snapshots.
 *
 * `customer_address_snapshot` is protected once an invoice is issued,
 * so the repair command is the only way to move it. That makes the
 * command part of the immutability contract, and it has to be tested:
 * it must not become a way around the guard.
 *
 * @see FixInvoiceAddressSnapshots
 */
class FixInvoiceAddressSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
    }

    private function invoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 0,
            'currency_code' => 'SAR',
            'customer_address_snapshot' => 'مبنى 1، شارع common.street، حي common.district',
        ], $overrides));
    }

    /**
     * The dry run must not write anything.
     */
    public function test_the_dry_run_changes_nothing(): void
    {
        $invoice = $this->invoice();

        $this->artisan('invoices:fix-address-snapshots --dry-run')
            ->assertSuccessful();

        $this->assertStringContainsString(
            'common.district',
            (string) $invoice->fresh()->customer_address_snapshot,
            'The dry run must not write.'
        );
    }

    /**
     * The repair fixes the snapshot on an issued invoice — this is the
     * path the guard would otherwise refuse.
     */
    public function test_it_repairs_an_issued_invoice_snapshot(): void
    {
        $invoice = $this->invoice();

        $this->artisan('invoices:fix-address-snapshots')
            ->assertSuccessful();

        $address = (string) $invoice->fresh()->customer_address_snapshot;

        $this->assertStringNotContainsString('common.district', $address);
        $this->assertStringNotContainsString('common.street', $address);
        $this->assertStringContainsString('حي', $address);
        $this->assertStringContainsString('شارع', $address);
    }

    /**
     * The repair must touch the address and NOTHING else. The amounts,
     * the tax and the status of an issued document are still frozen.
     */
    public function test_it_changes_nothing_but_the_address(): void
    {
        $invoice = $this->invoice();

        $before = [
            'total_tax' => (float) $invoice->total_tax,
            'total_incl_tax' => (float) $invoice->total_incl_tax,
            'status' => $invoice->status,
            'invoice_number' => $invoice->invoice_number,
        ];

        $this->artisan('invoices:fix-address-snapshots')->assertSuccessful();

        $fresh = $invoice->fresh();

        $this->assertSame($before['total_tax'], (float) $fresh->total_tax);
        $this->assertSame($before['total_incl_tax'], (float) $fresh->total_incl_tax);
        $this->assertSame($before['status'], $fresh->status);
        $this->assertSame($before['invoice_number'], $fresh->invoice_number);
    }

    /**
     * Every write must leave an audit trail — the bypass of the
     * immutability guard may never be silent.
     */
    public function test_it_writes_an_audit_log_entry(): void
    {
        $invoice = $this->invoice();

        Log::spy();

        $this->artisan('invoices:fix-address-snapshots')->assertSuccessful();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => $message === 'invoice.address_snapshot.repaired'
                && ($context['invoice_id'] ?? null) === $invoice->id
                && isset($context['before'], $context['after']));
    }

    /**
     * The repair is tenant-scoped: an operator who names a tenant must
     * not reach another tenant's documents.
     */
    public function test_it_is_tenant_scoped(): void
    {
        $mine = $this->invoice();

        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
        ]);

        $theirs = Invoice::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 0,
            'currency_code' => 'SAR',
            'customer_address_snapshot' => 'حي common.district',
        ]);

        $this->artisan('invoices:fix-address-snapshots --tenant='.$this->tenant->id)
            ->assertSuccessful();

        $this->assertStringNotContainsString(
            'common.district',
            (string) $mine->fresh()->customer_address_snapshot,
            'The named tenant must be repaired.'
        );

        $this->assertStringContainsString(
            'common.district',
            (string) $theirs->fresh()->customer_address_snapshot,
            'Another tenant must NOT be touched.'
        );
    }

    /**
     * A snapshot that holds something we do not recognise is left alone
     * rather than half-repaired.
     */
    public function test_it_leaves_an_unrecognised_snapshot_alone(): void
    {
        $invoice = $this->invoice([
            'customer_address_snapshot' => 'حي common.unknown_key',
        ]);

        $this->artisan('invoices:fix-address-snapshots')->assertSuccessful();

        $this->assertSame(
            'حي common.unknown_key',
            (string) $invoice->fresh()->customer_address_snapshot,
            'An unrecognised snapshot must not be rewritten.'
        );
    }
}
