<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Shift;
use App\Support\TenancyContext;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color' => 'nullable|string|max:7',
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
            'break_minutes' => 'nullable|integer|min:0|max:180',
        ]);

        Shift::create([
            'tenant_id' => TenancyContext::tenantId(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    public function update(Request $request, Shift $shift)
    {
        if ($shift->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color' => 'nullable|string|max:7',
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
            'break_minutes' => 'nullable|integer|min:0|max:180',
        ]);

        $shift->update($validated);

        return back()->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Shift $shift)
    {
        if ($shift->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Check if shift is used by any employees
        if ($shift->employees()->exists()) {
            return back()->withErrors(['error' => __('hr.shifts.cannot_delete_in_use')]);
        }

        $shift->delete();
        return back()->with('success', __('messages.deleted_successfully'));
    }
}
