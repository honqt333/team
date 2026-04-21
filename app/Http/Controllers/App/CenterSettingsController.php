<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\CenterWorkingHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CenterSettingsController extends Controller
{
    /**
     * Display center settings.
     */
    public function index(Center $center): Response
    {
        // Ensure center belongs to current tenant
        $this->authorize('view', $center);
        
        $center->load(['address', 'workingHours']);
        
        // Initialize working hours if not exists
        if ($center->workingHours->isEmpty()) {
            $this->initializeWorkingHours($center);
            $center->load('workingHours');
        }

        return Inertia::render('Settings/Centers/Show', [
            'center' => [
                'id' => $center->id,
                'name' => $center->name,
                'name_ar' => $center->name_ar,
                'name_en' => $center->name_en,
                'is_active' => $center->is_active,
                'is_main' => $center->is_main ?? false,
                // Can only modify/delete main center if there are other centers
                'canModifyMain' => $center->tenant->centers()->count() > 1,
            ],
            'profile' => [
                'name_ar' => $center->name_ar,
                'name_en' => $center->name_en,
                'manager_name' => $center->manager_name,
                'center_type' => $center->center_type,
                'license_number' => $center->license_number,
                'vat_number' => $center->tenant->vat_number, // Read-only from tenant
            ],
            'contact' => [
                'phone' => $center->phone,
                'email' => $center->email,
            ],
            'address' => $center->address ? [
                'address_line' => $center->address->address_line,
                'street' => $center->address->street,
                'city' => $center->address->city,
                'district' => $center->address->district,
                'building_number' => $center->address->building_number,
                'postal_code' => $center->address->postal_code,
                'latitude' => $center->address->latitude,
                'longitude' => $center->address->longitude,
            ] : null,
            'branding' => [
                'logo_light_url' => $center->logo_light_url,
                'logo_dark_url' => $center->logo_dark_url,
                'logo_invoice_url' => $center->logo_invoice_url,
                'stamp_url' => $center->stamp_url,
            ],
            'working_hours' => $center->workingHours->map(fn ($wh) => [
                'day_of_week' => $wh->day_of_week,
                'day_name' => CenterWorkingHour::getDayName($wh->day_of_week),
                'is_open' => $wh->is_open,
                'open_time' => $wh->open_time,
                'close_time' => $wh->close_time,
            ]),
        ]);
    }

    /**
     * Update center settings by section.
     */
    public function update(Request $request, Center $center)
    {
        $this->authorize('update', $center);
        
        $section = $request->input('section');
        
        switch ($section) {
            case 'profile':
                $this->updateProfile($request, $center);
                break;
            case 'contact':
                $this->updateContact($request, $center);
                break;
            case 'address':
                $this->updateAddress($request, $center);
                break;
            case 'working_hours':
                $this->updateWorkingHours($request, $center);
                break;
            default:
                return back()->withErrors(['section' => 'Invalid section']);
        }

        return back()->with('success', __('تم الحفظ بنجاح'));
    }

    private function updateProfile(Request $request, Center $center): void
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'manager_name' => 'nullable|string|max:255',
            'center_type' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:50',
        ]);

        $center->update($validated);
        
        // Also update main name
        $center->name = $validated['name_ar'];
        $center->save();
    }

    private function updateContact(Request $request, Center $center): void
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $center->update($validated);
    }

    private function updateAddress(Request $request, Center $center): void
    {
        $validated = $request->validate([
            'address_line' => 'nullable|string|max:500',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'building_number' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $center->address()->updateOrCreate(
            ['center_id' => $center->id],
            $validated
        );
    }

    private function updateWorkingHours(Request $request, Center $center): void
    {
        $validated = $request->validate([
            'working_hours' => 'required|array',
            'working_hours.*.day_of_week' => 'required|integer|between:0,6',
            'working_hours.*.is_open' => 'required|boolean',
            'working_hours.*.open_time' => 'nullable|date_format:H:i',
            'working_hours.*.close_time' => 'nullable|date_format:H:i',
        ]);

        $workingHours = $validated['working_hours'];

        // Validate logic: Close time must be after open time if both present
        foreach ($workingHours as $wh) {
            if ($wh['is_open'] && $wh['open_time'] && $wh['close_time']) {
                $open = \Carbon\Carbon::createFromFormat('H:i', $wh['open_time']);
                $close = \Carbon\Carbon::createFromFormat('H:i', $wh['close_time']);
                
                // If close is before or equal open (and not covering next day logic which we imply for now is same day)
                // Assuming simple same-day shifts for now.
                if ($close->lte($open)) {
                     // For shifts crossing midnight, logic differs, but user asked for "close > open" check
                     // Check if users meant cross-midnight? Usually shops close same day.
                     // But if 10 PM to 2 AM? 
                     // User said: "add check (close>open)". This strictly implies close MUST be greater.
                     throw \Illuminate\Validation\ValidationException::withMessages([
                        'working_hours' => __('Ensure close time is after open time')
                     ]);
                }
            }
        }

        foreach ($validated['working_hours'] as $wh) {
            CenterWorkingHour::updateOrCreate(
                [
                    'center_id' => $center->id,
                    'day_of_week' => $wh['day_of_week'],
                ],
                [
                    'is_open' => $wh['is_open'],
                    'open_time' => $wh['open_time'],
                    'close_time' => $wh['close_time'],
                ]
            );
        }
    }

    /**
     * Upload center logo.
     */
    public function uploadLogo(Request $request, Center $center)
    {
        $this->authorize('update', $center);
        
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'type' => 'required|in:light,dark,invoice',
        ]);

        $type = $request->input('type');
        $pathField = "logo_{$type}_path";
        
        // Delete old logo if exists
        if ($center->$pathField) {
            Storage::delete($center->$pathField);
        }

        $path = $request->file('logo')->store("centers/{$center->id}/logos", 'public');
        $center->update([$pathField => $path]);

        return back()->with('success', __('تم رفع الشعار بنجاح'));
    }

    /**
     * Delete center logo.
     */
    public function deleteLogo(Request $request, Center $center)
    {
        $this->authorize('update', $center);
        
        $request->validate([
            'type' => 'required|in:light,dark,invoice',
        ]);

        $type = $request->input('type');
        $pathField = "logo_{$type}_path";
        
        if ($center->$pathField) {
            Storage::delete($center->$pathField);
            $center->update([$pathField => null]);
        }

        return back()->with('success', __('تم حذف الشعار'));
    }

    /**
     * Upload center stamp.
     */
    public function uploadStamp(Request $request, Center $center)
    {
        $this->authorize('update', $center);
        
        $request->validate([
            'stamp' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        // Delete old stamp if exists
        if ($center->stamp_path) {
            Storage::delete($center->stamp_path);
        }

        $path = $request->file('stamp')->store("centers/{$center->id}/stamp", 'public');
        $center->update(['stamp_path' => $path]);

        return back()->with('success', __('تم رفع الختم بنجاح'));
    }

    /**
     * Delete center stamp.
     */
    public function deleteStamp(Center $center)
    {
        $this->authorize('update', $center);
        
        if ($center->stamp_path) {
            Storage::delete($center->stamp_path);
            $center->update(['stamp_path' => null]);
        }

        return back()->with('success', __('تم حذف الختم'));
    }

    /**
     * Initialize default working hours for a center.
     */
    private function initializeWorkingHours(Center $center): void
    {
        $defaultHours = [
            ['day_of_week' => 0, 'is_open' => true, 'open_time' => '08:00', 'close_time' => '17:00'], // Sunday
            ['day_of_week' => 1, 'is_open' => true, 'open_time' => '08:00', 'close_time' => '17:00'], // Monday
            ['day_of_week' => 2, 'is_open' => true, 'open_time' => '08:00', 'close_time' => '17:00'], // Tuesday
            ['day_of_week' => 3, 'is_open' => true, 'open_time' => '08:00', 'close_time' => '17:00'], // Wednesday
            ['day_of_week' => 4, 'is_open' => true, 'open_time' => '08:00', 'close_time' => '17:00'], // Thursday
            ['day_of_week' => 5, 'is_open' => false, 'open_time' => null, 'close_time' => null], // Friday (default off)
            ['day_of_week' => 6, 'is_open' => false, 'open_time' => null, 'close_time' => null], // Saturday (default off)
        ];

        foreach ($defaultHours as $hours) {
            $center->workingHours()->create($hours);
        }
    }
}
