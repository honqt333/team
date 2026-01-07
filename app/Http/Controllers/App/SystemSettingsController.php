<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    /**
     * Display the system settings page with side panel navigation.
     */
    /**
     * Display the system settings page with side panel navigation.
     */
    public function index(): Response
    {
        $makes = VehicleMake::ordered()->get();
        $tenant = auth()->user()->tenant;
        
        return Inertia::render('Settings/System/Index', [
            'makes' => $makes,
            'settings' => [
                'sms_2fa_enabled' => $tenant->sms_2fa_enabled,
            ],
            // Pass global setting to know if option should be available at all?
            // Actually, if global is disabled, even if tenant enables it, it won't work.
            // But good UX would be to hide/disable it if global is off.
            'global_sms_enabled' => \App\Models\Setting::get('security.2fa_sms_enabled', 'false') === 'true',
        ]);
    }

    /**
     * Update system settings (Tenant level).
     */
    public function update(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.sms_2fa_enabled' => 'boolean',
        ]);

        $tenant = auth()->user()->tenant;
        
        if (isset($request->settings['sms_2fa_enabled'])) {
            $tenant->update(['sms_2fa_enabled' => $request->settings['sms_2fa_enabled']]);
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
