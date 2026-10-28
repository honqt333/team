<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the customer signature of a quote (audit finding #1).
 *
 * The signature arrives as a data URI and used to be written to the
 * public disk with the file extension taken from the MIME type declared
 * inside that URI — and SVG was explicitly allowed. A data URI is
 * attacker-controlled: it can declare `image/svg+xml` and carry a
 * script, and the file was served straight from the public disk, so
 * opening the file URL ran that script inside the application's origin.
 * There was also no limit on the size of the payload.
 *
 * The signature pad produces PNG, so raster types only are accepted now,
 * the type is read from the bytes, and the extension written to disk
 * comes from that.
 */
class QuoteSignatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1x1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Customer $customer;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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

        foreach (['quotes.view', 'quotes.update', 'quotes.approve'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(['quotes.view', 'quotes.update', 'quotes.approve']);
    }

    private function quote(): Quote
    {
        return Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-SIG-001',
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(5)->format('Y-m-d'),
            'subtotal' => 200,
            'tax_total' => 30,
            'grand_total' => 230,
        ]);
    }

    private function svgDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">'
            .'<script>alert(document.domain)</script></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function test_an_svg_signature_does_not_approve_the_quote_publicly(): void
    {
        $quote = $this->quote();

        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => $this->svgDataUri(),
        ]);

        $response->assertSessionHasErrors('signature');

        $quote->refresh();

        $this->assertNotEquals(
            Quote::STATUS_APPROVED,
            $quote->status,
            'A quote must not be approved with a signature the server refused.'
        );
        $this->assertNull($quote->signature_path);
        $this->assertCount(0, Storage::disk('public')->files('signatures/quotes'));
    }

    public function test_an_svg_signature_is_refused_on_the_staff_route(): void
    {
        $quote = $this->quote();

        $response = $this->actingAsWithTeam($this->user)
            ->post(route('app.quotes.signature.store', $quote->id), [
                'signature' => $this->svgDataUri(),
            ]);

        $response->assertSessionHas('error');

        $quote->refresh();

        $this->assertNull($quote->signature_path);
        $this->assertCount(0, Storage::disk('public')->files('signatures/quotes'));
    }

    public function test_a_png_that_claims_to_be_an_svg_is_refused(): void
    {
        $quote = $this->quote();

        // The bytes are a PNG, the declared type says SVG.
        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => 'data:image/svg+xml;base64,'.self::PNG,
        ]);

        $response->assertSessionHasErrors('signature');

        $quote->refresh();

        $this->assertNull($quote->signature_path);
        $this->assertCount(0, Storage::disk('public')->files('signatures/quotes'));
    }

    public function test_a_payload_that_is_not_an_image_is_refused(): void
    {
        $quote = $this->quote();

        // Declared as a PNG, but the bytes are a script.
        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => 'data:image/png;base64,'.base64_encode('<?php echo "pwned"; ?>'),
        ]);

        $response->assertSessionHasErrors('signature');

        $quote->refresh();

        $this->assertNull($quote->signature_path);
        $this->assertCount(0, Storage::disk('public')->files('signatures/quotes'));
    }

    public function test_an_oversized_signature_is_refused(): void
    {
        $quote = $this->quote();

        // A PNG header followed by more than the accepted size.
        $oversized = base64_encode(random_bytes(600 * 1024));

        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => 'data:image/png;base64,'.$oversized,
        ]);

        $response->assertSessionHasErrors('signature');

        $quote->refresh();

        $this->assertNull($quote->signature_path);
        $this->assertCount(0, Storage::disk('public')->files('signatures/quotes'));
    }

    public function test_a_real_png_signature_is_stored_with_a_trusted_extension(): void
    {
        $quote = $this->quote();

        $response = $this->post("/view/quote/{$quote->uuid}/approve", [
            'signature' => 'data:image/png;base64,'.self::PNG,
        ]);

        $response->assertSessionHasNoErrors();

        $quote->refresh();

        $this->assertEquals(Quote::STATUS_APPROVED, $quote->status);
        $this->assertNotNull($quote->signature_path);

        // The extension comes from the bytes, and it is one of the three
        // accepted raster types — never something the request chose.
        $this->assertMatchesRegularExpression('/\.(png|jpg|webp)$/', $quote->signature_path);
        $this->assertStringEndsWith('.png', $quote->signature_path);

        Storage::disk('public')->assertExists($quote->signature_path);
        $this->assertSame(
            base64_decode(self::PNG),
            Storage::disk('public')->get($quote->signature_path),
            'The stored file must be exactly the bytes that were sent.'
        );
    }
}
