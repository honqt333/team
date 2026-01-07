<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSecurityController extends Controller
{
    /**
     * Update tenant 2FA settings.
     */
    public function update2FASettings(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'two_factor_enabled' => 'required|boolean',
            'two_factor_enforcement' => 'required|in:disabled,optional,required',
        ]);

        $tenant->update($validated);

        return back()->with('success', 'تم تحديث إعدادات المصادقة الثنائية');
    }
}
