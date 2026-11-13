<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\Media\ImageOptimizer;
use App\Services\System\SystemAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GeneralSettingsController extends Controller
{
    protected SystemAuditService $audit;

    public function __construct(
        ?SystemAuditService $audit = null
    ) {
        $this->audit = $audit ?? app(SystemAuditService::class);
    }

    public const ALLOWED_KEYS = [
        'security.2fa_sms_enabled',
        SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS,
    ];

    /**
     * Update a single general setting.
     */
    public function update(Request $request)
    {
        $this->authorizeSuperAdmin($request);

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

            $this->audit->log('general_setting_update', Setting::class, null, [
                'key' => $key,
                'value' => (int) $request->value,
            ], $request);

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

        $this->audit->log('general_setting_update', Setting::class, null, [
            'key' => $key,
            'value' => $value,
        ], $request);

        return back()->with('success', __('messages.system.settings.saved'));
    }

    /**
     * Upload or replace the platform-wide brand logo.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $this->deleteStoredBrandFile('system.brand_logo');

        $path = $request->file('logo')->store('brand', 'public');
        $disk = Storage::disk('public');
        $absolute = $disk->path($path);

        if (is_file($absolute)) {
            $binary = (string) file_get_contents($absolute);
            $optimised = ImageOptimizer::logo($binary);

            if ($optimised->binary !== '' && $optimised->binary !== $binary) {
                file_put_contents($absolute, $optimised->binary);
            }
        }

        $url = Storage::url($path);
        Setting::set('system.brand_logo', $url, 'general');

        if ($admin instanceof AdminUser) {
            $admin->logActivity('upload', 'GeneralSettings', null, ['key' => 'system.brand_logo', 'path' => $path]);
        }

        return back()->with('success', __('messages.system.settings.saved'));
    }

    /**
     * Remove the custom platform brand logo and revert to the built-in default.
     */
    public function removeLogo(Request $request): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $this->deleteStoredBrandFile('system.brand_logo');
        Setting::set('system.brand_logo', '', 'general');

        if ($admin instanceof AdminUser) {
            $admin->logActivity('delete', 'GeneralSettings', null, ['key' => 'system.brand_logo']);
        }

        return back()->with('success', __('messages.system.settings.saved'));
    }

    /**
     * Upload or replace the platform-wide browser favicon.
     */
    public function uploadFavicon(Request $request): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $request->validate([
            'favicon' => 'required|file|mimes:png,jpg,jpeg,webp,ico|max:1024',
        ]);

        $this->deleteStoredBrandFile('system.brand_favicon');

        $file = $request->file('favicon');
        $path = $file->store('brand', 'public');
        $disk = Storage::disk('public');
        $absolute = $disk->path($path);

        if (is_file($absolute) && strtolower($file->getClientOriginalExtension()) !== 'ico') {
            $binary = (string) file_get_contents($absolute);
            $optimised = ImageOptimizer::favicon($binary);

            if ($optimised->binary !== '' && $optimised->binary !== $binary) {
                file_put_contents($absolute, $optimised->binary);
            }
        }

        $url = Storage::url($path);
        Setting::set('system.brand_favicon', $url, 'general');

        if ($admin instanceof AdminUser) {
            $admin->logActivity('upload', 'GeneralSettings', null, ['key' => 'system.brand_favicon', 'path' => $path]);
        }

        return back()->with('success', __('messages.system.settings.saved'));
    }

    /**
     * Remove the custom platform favicon and revert to the built-in default.
     */
    public function removeFavicon(Request $request): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $this->deleteStoredBrandFile('system.brand_favicon');
        Setting::set('system.brand_favicon', '', 'general');

        if ($admin instanceof AdminUser) {
            $admin->logActivity('delete', 'GeneralSettings', null, ['key' => 'system.brand_favicon']);
        }

        return back()->with('success', __('messages.system.settings.saved'));
    }

    private function deleteStoredBrandFile(string $settingKey): void
    {
        $currentUrl = (string) Setting::get($settingKey, '');

        if ($currentUrl === '' || ! str_starts_with($currentUrl, '/storage/brand/')) {
            return;
        }

        $relativePath = ltrim(substr($currentUrl, strlen('/storage/')), '/');

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function authorizeSuperAdmin(Request $request): AdminUser|User
    {
        $user = $request->user('admin') ?? $request->user();
        $isSuperAdmin = ($user instanceof AdminUser && $user->isSuperAdmin())
            || ($user instanceof User && (bool) $user->is_system_admin);

        if (! $isSuperAdmin) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }

        return $user;
    }
}
