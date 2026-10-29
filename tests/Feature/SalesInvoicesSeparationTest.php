<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesInvoicesSeparationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(Permissions::INVOICES_VIEW);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
    }

    public function test_sales_index_separates_standard_sales_invoices_and_credit_notes(): void
    {
        // 1. Regular sales invoice
        $salesInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'type' => InvoiceType::INVOICE->value,
            'invoice_number' => 'INV-2026-0001',
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_incl_tax' => 500,
        ]);

        // 2. Return invoice / Credit note
        $creditNote = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'type' => InvoiceType::CREDIT_NOTE->value,
            'invoice_number' => 'CN-2026-0001',
            'original_invoice_id' => $salesInvoice->id,
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_incl_tax' => 150,
        ]);

        $response = $this->actingAs($this->user)->get(route('app.invoices.sales.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Sales/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $salesInvoice->id)
            ->where('invoices.data.0.invoice_number', 'INV-2026-0001')
            ->has('returns.data', 1)
            ->where('returns.data.0.id', $creditNote->id)
            ->where('returns.data.0.invoice_number', 'CN-2026-0001')
            ->where('returns.data.0.original_invoice.id', $salesInvoice->id)
        );
    }
}
