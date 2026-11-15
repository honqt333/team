<?php

declare(strict_types=1);

namespace Tests\Feature\LaunchAudit;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * LAUNCH AUDIT — the public, unauthenticated surface.
 *
 * Everything in this file is reachable by ANYONE on the internet who
 * knows the URL. That is where a mistake costs the most, because there
 * is no session and no tenant context to fall back on.
 *
 * The public quote link is resolved with `Quote::withoutGlobalScopes()`
 * (app/Http/Controllers/Public/PublicQuoteController.php:143) — by
 * design, since the link is the credential. The risk that creates is
 * that a link for tenant A can reach tenant B's data, so the contract
 * here is: a link exposes ITS quote and nothing else, and a decision
 * taken through it is final and is written to the audit trail once.
 */
class PublicSurfaceLifecycleAuditTest extends TestCase
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
            'name' => 'عميل عام',
        ]);
    }

    private function quote(bool $withLine = true, bool $expired = false): Quote
    {
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
        ]);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'PUB-'.bin2hex(random_bytes(3)),
            'status' => Quote::STATUS_SENT,
            'valid_until' => $expired ? now()->subDay() : now()->addDays(3),
        ]);

        if ($withLine) {
            QuoteLine::create([
                'quote_id' => $quote->id,
                'description' => 'خدمة فحص',
                'qty' => 1,
                'unit_price' => 150,
                'final_unit_price' => 150,
                'line_total' => 150,
            ]);
        }

        return $quote->fresh();
    }

    public function test_a_public_quote_link_exposes_that_quote_and_nothing_else(): void
    {
        $quote = $this->quote();

        $response = $this->get('/view/quote/'.$quote->uuid);

        $response->assertOk();
        $response->assertSee($quote->code);
    }

    public function test_an_unknown_quote_link_does_not_expose_anything(): void
    {
        $response = $this->get('/view/quote/'.fake()->uuid());

        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    public function test_the_public_decision_is_final(): void
    {
        $quote = $this->quote();

        $this->post('/view/quote/'.$quote->uuid.'/approve')
            ->assertSessionHas('success');

        $this->assertSame(Quote::STATUS_APPROVED, $quote->fresh()->status);

        // A second decision of any kind must be refused, and the quote
        // must stay approved.
        $this->post('/view/quote/'.$quote->uuid.'/reject', ['rejection_reason' => 'تغيّر الرأي'])
            ->assertSessionHas('error');

        $this->assertSame(
            Quote::STATUS_APPROVED,
            $quote->fresh()->status,
            'An approved quote must not be re-decided.'
        );
    }

    public function test_a_rejected_quote_cannot_be_approved_afterwards(): void
    {
        $quote = $this->quote();

        $this->post('/view/quote/'.$quote->uuid.'/reject', ['rejection_reason' => 'لا'])
            ->assertSessionHas('success');

        $this->assertSame(Quote::STATUS_REJECTED, $quote->fresh()->status);

        $this->post('/view/quote/'.$quote->uuid.'/approve')
            ->assertSessionHas('error');

        $this->assertSame(Quote::STATUS_REJECTED, $quote->fresh()->status);
    }

    public function test_an_expired_quote_cannot_be_decided(): void
    {
        $quote = $this->quote(true, true);

        $this->post('/view/quote/'.$quote->uuid.'/approve')
            ->assertSessionHas('error');

        $this->assertSame(
            Quote::STATUS_SENT,
            $quote->fresh()->status,
            'An expired quote must stay untouched.'
        );
    }

    /**
     * A quote with nothing in it must not be approvable — there is no
     * price to approve. This guard is what the repository's stale
     * `ThirdBatchSafetyTest` fixture trips over: it builds a quote with
     * no lines and then expects a successful approval.
     */
    public function test_a_quote_with_no_lines_cannot_be_approved(): void
    {
        $quote = $this->quote(false);

        $this->post('/view/quote/'.$quote->uuid.'/approve')
            ->assertSessionHas('error');

        $this->assertSame(Quote::STATUS_SENT, $quote->fresh()->status);
    }

    public function test_the_decision_is_written_to_the_audit_trail_once(): void
    {
        $quote = $this->quote();

        Log::spy();

        $this->post('/view/quote/'.$quote->uuid.'/approve')->assertSessionHas('success');

        Log::shouldHaveReceived('notice')
            ->once()
            ->with('quote.approve', Mockery::on(fn ($ctx) => $ctx['quote_id'] === $quote->id
                && $ctx['tenant_id'] === $quote->tenant_id
                && $ctx['channel'] === 'public_link'));
    }

    /**
     * The readiness probe is public. It must answer with a status and
     * nothing that names a host, a user or a secret.
     */
    public function test_the_readiness_probe_does_not_leak_internals(): void
    {
        $response = $this->get('/api/readyz');

        $body = $response->getContent();

        $this->assertStringNotContainsString('mysql://', $body);
        $this->assertStringNotContainsString('secret', strtolower($body));
        $this->assertStringNotContainsString('DB_PASSWORD', $body);
    }
}
