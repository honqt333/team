<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuotePart;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QuoteConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QuoteParityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Customer $customer;

    protected Vehicle $vehicle;

    protected Service $service;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
        ]);
        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'الميكانيكا',
            'name_en' => 'Mechanics',
        ]);
        $this->service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'department_id' => $this->department->id,
            'base_price' => 100,
            'min_price' => 80,
        ]);

        foreach (['quotes.view', 'quotes.create', 'quotes.update', 'quotes.delete', 'quotes.approve'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(['quotes.view', 'quotes.create', 'quotes.update', 'quotes.delete', 'quotes.approve']);
    }

    public function test_can_create_quote_with_valid_until_date(): void
    {
        $this->actingAsWithTeam($this->user);

        $validDate = now()->addDays(15)->format('Y-m-d');

        $response = $this->post('/app/quotes', [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'valid_until' => $validDate,
            'lines' => [
                [
                    'service_id' => $this->service->id,
                    'department_id' => $this->department->id,
                    'description' => 'Regular Maintenance',
                    'qty' => 1,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertRedirect();

        $quote = Quote::latest('id')->first();
        $this->assertNotNull($quote);
        $this->assertEquals($validDate, $quote->valid_until?->format('Y-m-d'));
        $this->assertFalse($quote->isExpired());
    }

    public function test_can_duplicate_quote(): void
    {
        $this->actingAsWithTeam($this->user);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-000001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_APPROVED,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
            'subtotal' => 150,
            'tax_total' => 22.5,
            'grand_total' => 172.5,
        ]);

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $this->service->id,
            'department_id' => $this->department->id,
            'description' => 'Oil Change',
            'qty' => 1,
            'unit_price' => 100,
        ]);

        QuotePart::create([
            'quote_id' => $quote->id,
            'quote_line_id' => $line->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Oil Filter',
            'qty' => 1,
            'unit_price' => 50,
            'source' => QuotePart::SOURCE_WAREHOUSE,
        ]);

        $response = $this->post("/app/quotes/{$quote->id}/duplicate");
        $response->assertRedirect();

        // Check new quote was created
        $duplicatedQuote = Quote::where('id', '!=', $quote->id)->latest('id')->first();
        $this->assertNotNull($duplicatedQuote);
        $this->assertNotEquals($quote->code, $duplicatedQuote->code);
        $this->assertEquals(Quote::STATUS_DRAFT, $duplicatedQuote->status);
        $this->assertEquals($this->customer->id, $duplicatedQuote->customer_id);
        $this->assertEquals($this->vehicle->id, $duplicatedQuote->vehicle_id);

        // Check lines and parts cloned
        $this->assertCount(1, $duplicatedQuote->lines);
        $this->assertEquals('Oil Change', $duplicatedQuote->lines->first()->description);
        $this->assertCount(1, $duplicatedQuote->parts);
        $this->assertEquals('Oil Filter', $duplicatedQuote->parts->first()->name);
    }

    public function test_can_export_quotes_to_excel(): void
    {
        $this->actingAsWithTeam($this->user);

        Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-100001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_DRAFT,
            'subtotal' => 100,
            'tax_total' => 15,
            'grand_total' => 115,
        ]);

        $response = $this->get('/app/quotes/export');
        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition', ''), 'attachment') ||
            str_contains($response->headers->get('content-type', ''), 'spreadsheet') ||
            str_contains($response->headers->get('content-type', ''), 'csv')
        );
    }

    public function test_customer_can_sign_and_approve_quote_publicly(): void
    {
        Storage::fake('public');

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-200001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(5)->format('Y-m-d'),
            'subtotal' => 200,
            'tax_total' => 30,
            'grand_total' => 230,
        ]);

        // 1x1 transparent PNG base64 data URI
        $dummySignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => $dummySignature,
        ]);

        $response->assertRedirect();

        $quote->refresh();
        $this->assertEquals(Quote::STATUS_APPROVED, $quote->status);
        $this->assertNotNull($quote->signature_path);
        Storage::disk('public')->assertExists($quote->signature_path);
        $this->assertNotNull($quote->signature_url);
    }

    public function test_cannot_approve_expired_quote(): void
    {
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-300001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->subDay()->format('Y-m-d'),
            'subtotal' => 200,
            'tax_total' => 30,
            'grand_total' => 230,
        ]);

        $this->assertTrue($quote->isExpired());

        $dummySignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => $dummySignature,
        ]);

        $response->assertSessionHas('error');
        $quote->refresh();
        $this->assertNotEquals(Quote::STATUS_APPROVED, $quote->status);
        $this->assertNull($quote->signature_path);
    }

    public function test_quote_conversion_copies_customer_signature_to_work_order(): void
    {
        Storage::fake('public');

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-400001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_APPROVED,
            'signature_path' => 'signatures/quotes/test_sig.png',
            'approved_at' => now(),
            'subtotal' => 100,
            'total' => 100,
        ]);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'description' => 'Test Service',
            'qty' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $conversionService = app(QuoteConversionService::class);
        $workOrder = $conversionService->convert($quote, $this->user);

        $this->assertNotNull($workOrder);
        $this->assertEquals('signatures/quotes/test_sig.png', $workOrder->reception_signature);
        $this->assertNotNull($workOrder->reception_signed_at);
    }

    public function test_staff_can_save_customer_signature_directly_on_quote(): void
    {
        Storage::fake('public');
        $this->actingAsWithTeam($this->user);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-500001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_DRAFT,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $dummySignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->post(route('app.quotes.signature.store', $quote->id), [
            'signature' => $dummySignature,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $quote->refresh();
        $this->assertNotNull($quote->signature_path);
        Storage::disk('public')->assertExists($quote->signature_path);
        $this->assertNotNull($quote->signature_url);
    }

    public function test_public_quote_rejects_empty_quote_and_soft_deleted_tenant(): void
    {
        $emptyQuote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-EMPTY-001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(5)->format('Y-m-d'),
            'subtotal' => 0,
            'total' => 0,
        ]);

        $this->get("/view/quote/{$emptyQuote->uuid}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Quotes/PublicShow')
                ->where('quote.can_be_actioned', false)
            );

        $this->post("/view/quote/{$emptyQuote->uuid}/approve")
            ->assertSessionHas('error', __('quotes.empty_decision'));

        $this->post("/view/quote/{$emptyQuote->uuid}/reject", [
            'rejection_reason' => 'Empty quote test',
        ])->assertSessionHas('error', __('quotes.empty_decision'));

        $emptyQuote->refresh();
        $this->assertSame(Quote::STATUS_SENT, $emptyQuote->status);

        // Soft-deleting the tenant makes the public quote link return 404
        $this->tenant->delete();

        $this->get("/view/quote/{$emptyQuote->uuid}")
            ->assertNotFound();

        $this->post("/view/quote/{$emptyQuote->uuid}/approve")
            ->assertNotFound();
    }
}
