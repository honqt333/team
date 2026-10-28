<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Integration\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the provider-credential leak on the integrations screen
 * (audit finding #32).
 *
 * THE BUG
 *
 * `IntegrationsController::index()` fetched the whole Integration
 * model — including the `config` JSON column — and passed it to
 * Inertia:
 *
 *   $integrations = Integration::orderBy('type')->...->get();
 *   return Inertia::render('System/Integrations/Index', [...]);
 *
 * The detail action `show()` had a redaction helper, but the LIST
 * action never called it, so every provider secret was readable in
 * the page payload of anyone who opened the integrations screen:
 *
 *   - Twilio      auth_token
 *   - 360dialog  api_key
 *   - WhatsApp    access_token
 *   - SMTP        password
 *   - Unifonic    app_sid  (sent as AppSid on every API call)
 *
 * A second, related gap: the redaction helper only masked fields
 * declared `type: 'password'`. Credentials that the form renders as
 * plain text — Unifonic's `app_sid` and Twilio's `account_sid` — are
 * authenticated values and were still readable.
 *
 * A third, latent bug found while fixing the above: the redaction
 * placeholder is echoed back by the form on save. The update action
 * only preserved the stored value when the incoming field was EMPTY,
 * so saving the settings page replaced the live secret with the
 * literal mask string and broke the provider integration.
 *
 * THE FIX
 *
 *   1. index() selects an explicit column list that excludes `config`
 *      — the secret is never queried, so it cannot leak.
 *   2. `getConfigFields()` gained a `sensitive` flag for credentials
 *      that are not typed as password, and the redaction honours it.
 *   3. update() treats the placeholder as "unchanged" and keeps the
 *      stored value.
 */
class IntegrationSecretsLeakTest extends TestCase
{
    use RefreshDatabase;

    private const TWILIO_TOKEN = 'twilio-auth-token-SUPER-SECRET';

    private const UNIFONIC_SID = 'unifonic-app-sid-SUPER-SECRET';

    private const SMTP_PASSWORD = 'smtp-password-SUPER-SECRET';

    protected function setUp(): void
    {
        parent::setUp();

        Integration::create([
            'type' => 'sms',
            'provider' => 'twilio',
            'name' => 'Twilio',
            'name_ar' => 'تويليو',
            'config' => [
                'account_sid' => 'AC-twilio-account-sid',
                'auth_token' => self::TWILIO_TOKEN,
                'from_number' => '+966500000000',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        Integration::create([
            'type' => 'sms',
            'provider' => 'unifonic',
            'name' => 'Unifonic',
            'name_ar' => 'يونيفونك',
            'config' => [
                'app_sid' => self::UNIFONIC_SID,
                'sender_id' => 'ProService',
            ],
            'is_active' => false,
        ]);

        Integration::create([
            'type' => 'email',
            'provider' => 'smtp',
            'name' => 'SMTP',
            'name_ar' => 'بريد',
            'config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'mailer@example.com',
                'password' => self::SMTP_PASSWORD,
                'from_address' => 'noreply@example.com',
            ],
            'is_active' => false,
        ]);
    }

    /**
     * A system admin account able to reach the /system panel.
     */
    private function systemAdmin(): User
    {
        return User::factory()->create([
            'is_system_admin' => true,
        ]);
    }

    /**
     * The headline leak: the list screen must not carry any secret.
     */
    public function test_the_integrations_list_does_not_leak_provider_secrets(): void
    {
        $admin = $this->systemAdmin();

        $response = $this->actingAs($admin)->get('/system/integrations');

        $response->assertOk();

        $payload = $response->getContent();

        $this->assertStringNotContainsString(
            self::TWILIO_TOKEN,
            $payload,
            'The Twilio auth token must not appear in the integrations list payload.'
        );
        $this->assertStringNotContainsString(
            self::UNIFONIC_SID,
            $payload,
            'The Unifonic App SID must not appear in the integrations list payload.'
        );
        $this->assertStringNotContainsString(
            self::SMTP_PASSWORD,
            $payload,
            'The SMTP password must not appear in the integrations list payload.'
        );
    }

    /**
     * The list must not carry the config column at all — masking each
     * known key would be a whack-a-mole; not selecting the column is
     * the structural fix.
     */
    public function test_the_integrations_list_does_not_carry_the_config_column_at_all(): void
    {
        $admin = $this->systemAdmin();

        $response = $this->actingAs($admin)->get('/system/integrations');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertIsArray($page['props']['integrations']);

        foreach ($page['props']['integrations'] as $group) {
            foreach ($group as $integration) {
                $this->assertArrayNotHasKey(
                    'config',
                    $integration,
                    'The integrations list must not carry the config column into the browser.'
                );
            }
        }
    }

    /**
     * The list still has to work — it drives the catalogue UI.
     */
    public function test_the_integrations_list_still_renders_the_catalogue(): void
    {
        $admin = $this->systemAdmin();

        $response = $this->actingAs($admin)->get('/system/integrations');

        $response->assertOk();

        $page = $response->viewData('page');
        $providers = array_map(fn ($group) => count($group), $page['props']['integrations']);

        $this->assertSame(2, $providers['sms'], 'Both SMS integrations must still be listed.');
        $this->assertSame(1, $providers['email'], 'The SMTP integration must still be listed.');
    }

    /**
     * The detail screen masks the password-typed secret.
     */
    public function test_the_integration_detail_masks_a_password_typed_secret(): void
    {
        $admin = $this->systemAdmin();
        $twilio = Integration::where('provider', 'twilio')->firstOrFail();

        $response = $this->actingAs($admin)->get('/system/integrations/'.$twilio->id);

        $response->assertOk();

        $payload = $response->getContent();

        $this->assertStringNotContainsString(
            self::TWILIO_TOKEN,
            $payload,
            'The Twilio auth token must not appear in the integration detail payload.'
        );
    }

    /**
     * ...and also the credentials that are NOT typed as password but
     * are still authenticated values.
     */
    public function test_the_integration_detail_masks_credentials_that_are_not_password_typed(): void
    {
        $admin = $this->systemAdmin();
        $unifonic = Integration::where('provider', 'unifonic')->firstOrFail();

        $response = $this->actingAs($admin)->get('/system/integrations/'.$unifonic->id);

        $response->assertOk();

        $payload = $response->getContent();

        $this->assertStringNotContainsString(
            self::UNIFONIC_SID,
            $payload,
            'The Unifonic App SID authenticates the API call and must not appear in the payload.'
        );
    }

    /**
     * Non-sensitive configuration stays visible — the operator needs it
     * to know what is configured (host, port, sender id, ...).
     */
    public function test_the_integration_detail_still_shows_non_sensitive_configuration(): void
    {
        $admin = $this->systemAdmin();
        $smtp = Integration::where('provider', 'smtp')->firstOrFail();

        $response = $this->actingAs($admin)->get('/system/integrations/'.$smtp->id);

        $response->assertOk();

        $page = $response->viewData('page');
        $config = $page['props']['integration']['config'];

        $this->assertSame('smtp.example.com', $config['host']);
        $this->assertSame('mailer@example.com', $config['username']);
        $this->assertSame('noreply@example.com', $config['from_address']);
    }

    /**
     * The round-trip bug: saving the settings page without retyping
     * the secret must not store the mask as the live credential.
     */
    public function test_saving_the_form_without_retyping_the_secret_preserves_the_stored_value(): void
    {
        $admin = $this->systemAdmin();
        $twilio = Integration::where('provider', 'twilio')->firstOrFail();

        // This is exactly what the browser sends: the form is
        // initialised from the masked payload, so the mask comes back
        // unchanged unless the operator types a new secret.
        $response = $this->actingAs($admin)->put('/system/integrations/'.$twilio->id, [
            'is_active' => true,
            'is_default' => true,
            'purpose' => 'all',
            'config' => [
                'account_sid' => '••••••••',
                'auth_token' => '••••••••',
                'from_number' => '+966500000000',
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $fresh = $twilio->fresh();

        $this->assertSame(
            self::TWILIO_TOKEN,
            $fresh->config['auth_token'],
            'Saving the form must not replace the stored auth token with the redaction mask.'
        );
        $this->assertSame(
            'AC-twilio-account-sid',
            $fresh->config['account_sid'],
            'Saving the form must not replace the stored account SID with the redaction mask.'
        );
    }

    /**
     * ...and an operator who DOES type a new secret still replaces it.
     */
    public function test_saving_the_form_with_a_new_secret_replaces_the_stored_value(): void
    {
        $admin = $this->systemAdmin();
        $twilio = Integration::where('provider', 'twilio')->firstOrFail();

        $response = $this->actingAs($admin)->put('/system/integrations/'.$twilio->id, [
            'is_active' => true,
            'is_default' => true,
            'purpose' => 'all',
            'config' => [
                'account_sid' => 'AC-new-account-sid',
                'auth_token' => 'brand-new-token',
                'from_number' => '+966500000000',
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame('brand-new-token', $twilio->fresh()->config['auth_token']);
    }
}
