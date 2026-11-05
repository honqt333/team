<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    /**
     * Strict allowlist of the setting keys this controller may write.
     *
     * The `settings` table is a platform-wide key/value store shared by
     * every settings surface: the website CMS (including
     * `website.scripts.header` / `website.chatbot.script`, which are
     * injected into public pages), the help centre, and the landing
     * page. Writing it by an arbitrary client-supplied key would let a
     * single request overwrite ANY of those — including injecting a
     * script tag into the public site or clobbering SMTP credentials.
     *
     * The General Settings page currently exposes exactly one toggle,
     * so the allowlist is deliberately that narrow. Adding a new
     * setting means adding it here first — that is the point.
     */
    public const ALLOWED_KEYS = [
        'security.2fa_sms_enabled',
    ];

    /**
     * Display a listing of tenants.
     */
    public function index(): Response
    {
        return Inertia::render('System/Settings/GeneralSettings', [
            'settings' => [
                'security_2fa_sms_enabled' => Setting::get('security.2fa_sms_enabled', 'false') === 'true',
            ],
        ]);
    }

    /**
     * Update a single general setting.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'present',
        ]);

        // Reject anything outside the allowlist BEFORE touching the
        // database. `Rule::in()` would work too, but an explicit check
        // gives a clearer error and keeps the allowlist readable.
        if (! in_array($validated['key'], self::ALLOWED_KEYS, true)) {
            throw ValidationException::withMessages([
                'key' => __('validation.in', ['attribute' => 'key']),
            ]);
        }

        // Convert boolean to 'true'/'false' string
        $value = $request->value;

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === true || $value === 'true' || $value === '1' || $value === 1) {
            $value = 'true';
        } else {
            $value = 'false';
        }

        Setting::set($validated['key'], $value, 'security');

        return back()->with('success', __('messages.system.settings.saved'));
    }
}
