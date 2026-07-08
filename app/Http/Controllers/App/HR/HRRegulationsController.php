<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\HRRegulation;
use App\Support\TenancyContext;
use Illuminate\Http\Request;

class HRRegulationsController extends Controller
{
    /**
     * Store new regulation.
     */
    public function storeRegulation(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_ar' => 'required|string',
            'content_en' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        HRRegulation::create([
            'tenant_id' => TenancyContext::tenantId(),
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    /**
     * Update regulation.
     */
    public function updateRegulation(Request $request, HRRegulation $regulation)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_ar' => 'required|string',
            'content_en' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $regulation->update([
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Delete regulation.
     */
    public function destroyRegulation(HRRegulation $regulation)
    {
        $regulation->delete();
        return back()->with('success', __('messages.deleted_successfully'));
    }
}
