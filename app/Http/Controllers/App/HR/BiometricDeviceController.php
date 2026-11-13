<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\BiometricDevice;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiometricDeviceController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize(Permissions::HR_SETTINGS_MANAGE);

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'center_id' => ['required', Rule::exists('centers', 'id')->where('tenant_id', $tenantId)],
            'device_id' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $device = BiometricDevice::create([
            'tenant_id' => auth()->user()->tenant_id,
            'center_id' => $validated['center_id'],
            'name' => $validated['name'],
            'device_id' => $validated['device_id'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', __('common.saved_success'))
            ->with('new_device_token', $device->api_token);
    }

    public function update(Request $request, BiometricDevice $biometricDevice)
    {
        $this->authorize(Permissions::HR_SETTINGS_MANAGE);

        if ($biometricDevice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'center_id' => ['required', Rule::exists('centers', 'id')->where('tenant_id', $tenantId)],
            'device_id' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $biometricDevice->update($validated);

        return back()->with('success', __('common.saved_success'));
    }

    public function destroy(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $biometricDevice->delete();

        return back()->with('success', __('common.deleted_success'));
    }

    public function regenerateToken(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $newToken = $biometricDevice->regenerateToken();

        return back()->with('success', __('common.saved_success'))
            ->with('new_device_token', $newToken);
    }

    /**
     * Issue a fresh token for the device and return it once.
     *
     * The token is stored as a hash (see the 2026_10_25_191000
     * migration), so it cannot be read back from the database — that
     * is the whole point of hashing it. The previous implementation of
     * this endpoint returned the stored plaintext, which meant the
     * secret was sitting in the database in recoverable form.
     *
     * Asking for the token therefore rotates it: the old token stops
     * working immediately and the new one is returned exactly once,
     * for the operator to copy into the device.
     */
    public function showToken(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return back()
            ->with('success', __('common.saved_success'))
            ->with('new_device_token', $biometricDevice->regenerateToken());
    }
}
