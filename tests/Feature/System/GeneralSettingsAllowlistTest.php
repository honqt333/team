<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Http\Controllers\System\GeneralSettingsController;
use App\Models\AdminUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The General Settings page must not be able to write arbitrary rows
 * into the platform-wide `settings` table.
 *
 * The `settings` table backs every settings surface at once — the
 * website CMS (including `website.scripts.header` and
 * `website.chatbot.script`, which are injected into public pages), the
 * help centre, and the landing page. The endpoint used to take the
 * key straight from the request body, so one crafted PUT could
 * overwrite any of them, including injecting script into the public
 * site.
 *
 * The allowlist is enforced server-side; hiding the input in the UI is
 * not a control.
 */
class GeneralSettingsAllowlistTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Settings Admin',
            'email' => 'settings-admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_the_allowed_key_can_be_updated(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('system.settings.general.update'), [
                'key' => 'security.2fa_sms_enabled',
                'value' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame('true', Setting::get('security.2fa_sms_enabled'));
    }

    public function test_the_allowed_key_can_be_set_to_false(): void
    {
        Setting::set('security.2fa_sms_enabled', 'true', 'security');

        $this->actingAs($this->admin, 'admin')
            ->put(route('system.settings.general.update'), [
                'key' => 'security.2fa_sms_enabled',
                'value' => false,
            ])
            ->assertRedirect();

        $this->assertSame('false', Setting::get('security.2fa_sms_enabled'));
    }

    public function test_an_unknown_key_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('system.settings.general'))
            ->put(route('system.settings.general.update'), [
                'key' => 'some.arbitrary.setting',
                'value' => 'hijacked',
            ]);

        $response->assertSessionHasErrors('key');

        $this->assertNull(
            Setting::get('some.arbitrary.setting'),
            'A key outside the allowlist must never reach the settings table.'
        );
    }

    public function test_a_public_script_setting_cannot_be_overwritten_through_this_endpoint(): void
    {
        // The sharpest case: `website.scripts.header` is injected into
        // every public page, so writing it from the general settings
        // endpoint would be a stored-XSS vector against the public site.
        Setting::set('website.scripts.header', '<script>original()</script>', 'website');

        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('system.settings.general'))
            ->put(route('system.settings.general.update'), [
                'key' => 'website.scripts.header',
                'value' => '<script>evil()</script>',
            ]);

        $response->assertSessionHasErrors('key');

        $this->assertSame(
            '<script>original()</script>',
            Setting::get('website.scripts.header'),
            'The public script tag must be untouched.'
        );
    }

    public function test_a_chatbot_script_setting_cannot_be_overwritten_through_this_endpoint(): void
    {
        Setting::set('website.chatbot.script', 'original', 'website');

        $this->actingAs($this->admin, 'admin')
            ->from(route('system.settings.general'))
            ->put(route('system.settings.general.update'), [
                'key' => 'website.chatbot.script',
                'value' => 'evil',
            ])
            ->assertSessionHasErrors('key');

        $this->assertSame('original', Setting::get('website.chatbot.script'));
    }

    public function test_a_key_from_another_group_cannot_be_overwritten(): void
    {
        Setting::set('help.contact.email', 'help@example.test', 'help');

        $this->actingAs($this->admin, 'admin')
            ->from(route('system.settings.general'))
            ->put(route('system.settings.general.update'), [
                'key' => 'help.contact.email',
                'value' => 'attacker@example.test',
            ])
            ->assertSessionHasErrors('key');

        $this->assertSame('help@example.test', Setting::get('help.contact.email'));
    }

    public function test_the_allowlist_contains_only_the_key_the_page_uses(): void
    {
        // The Vue page (GeneralSettings.vue) sends exactly one key. If
        // the allowlist drifts, one of the two sides needs updating.
        $this->assertSame(
            ['security.2fa_sms_enabled'],
            GeneralSettingsController::ALLOWED_KEYS,
            'The allowlist must match the keys the page actually sends.'
        );
    }
}
