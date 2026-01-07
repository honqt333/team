<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeneralSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Settings/GeneralSettings', [
            'settings' => [
                'security_2fa_sms_enabled' => Setting::get('security.2fa_sms_enabled', 'false') === 'true',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'present',
        ]);

        // Convert boolean to 'true'/'false' string
        $value = $request->value;
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === true || $value === 'true' || $value === '1' || $value === 1) {
            $value = 'true';
        } else {
            $value = 'false';
        }

        Setting::set($request->key, $value, 'security');

        return back()->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}
