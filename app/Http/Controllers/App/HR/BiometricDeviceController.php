<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\BiometricDevice;
use Illuminate\Http\Request;

class BiometricDeviceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'center_id' => 'required|exists:centers,id',
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
        if ($biometricDevice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'center_id' => 'required|exists:centers,id',
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

    public function showToken(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return response()->json([
            'token' => $biometricDevice->api_token,
        ]);
    }
}
