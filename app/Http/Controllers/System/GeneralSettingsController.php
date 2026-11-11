<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public const ALLOWED_KEYS = [
        'security.2fa_sms_enabled',
        SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS,
    ];

    /**
     * Display general system settings.
     */
    public function index(SubscriptionLifecycleService $lifecycle): Response
    {
        return Inertia::render('System/Settings/GeneralSettings', [
            'settings' => [
                'security_2fa_sms_enabled' => Setting::get('security.2fa_sms_enabled', 'false') === 'true',
                'subscription_grace_period_days' => $lifecycle->getGracePeriodDays(),
            ],
        ]);
    }

    /**
     * Update a single general setting.
     */
    public function update(Request $request)
    {
        $user = $request->user('admin') ?? $request->user();
        $isSuperAdmin = ($user instanceof AdminUser && $user->isSuperAdmin())
            || ($user instanceof User && (bool) $user->is_system_admin);

        if (! $isSuperAdmin) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }

        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'present',
        ]);

        if (! in_array($validated['key'], self::ALLOWED_KEYS, true)) {
            throw ValidationException::withMessages([
                'key' => __('validation.in', ['attribute' => 'key']),
            ]);
        }

        $key = $validated['key'];

        if ($key === SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS) {
            $request->validate([
                'value' => 'required|integer|min:0|max:365',
            ]);

            Setting::set($key, (string) ((int) $request->value), 'subscription');

            return back()->with('success', __('messages.system.settings.saved'));
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

        Setting::set($key, $value, 'security');

        return back()->with('success', __('messages.system.settings.saved'));
    }
}
