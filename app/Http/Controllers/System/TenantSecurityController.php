<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\System\SystemAuditService;
use Illuminate\Http\Request;

class TenantSecurityController extends Controller
{
    protected SystemAuditService $audit;

    public function __construct(
        ?SystemAuditService $audit = null
    ) {
        $this->audit = $audit ?? app(SystemAuditService::class);
    }

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

        $this->audit->log('tenant_security_2fa_update', Tenant::class, $tenant->id, $validated, $request);

        return back()->with('success', __('messages.system.security.two_factor_updated'));
    }
}
