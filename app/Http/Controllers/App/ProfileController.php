<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\TwoFactorService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class ProfileController extends Controller
{
    protected TwoFactorService $twoFactor;

    public function __construct(TwoFactorService $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    /**
     * Display the user's profile.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $isEnabled = $user->two_factor_confirmed_at !== null;

        // Linked employee information
        $employee = $user->employee()
            ->with(['jobTitle:id,name_ar,name_en', 'department:id,name_ar,name_en'])
            ->first();

        $jobTitle = $employee?->jobTitle?->name_ar ?: ($employee?->jobTitle?->name_en ?: null);
        $department = $employee?->department?->name_ar ?: ($employee?->department?->name_en ?: null);

        // Roles & Permissions
        $roles = $user->roles()->get(['id', 'name', 'label_ar', 'label_en'])->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'label' => $r->label_ar ?: $r->name,
            ];
        });

        $isFullAccess = $user->hasRole('super_admin') || (bool) ($user->is_system_admin ?? false);

        $permissions = $user->getAllPermissions()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->label_ar ?: $p->name,
            ];
        });

        // User preferences from tenant settings
        $rawPrefs = $user->tenant_id ? Setting::forTenant($user->tenant_id, 'user_preferences.'.$user->id) : null;
        $savedPrefs = $rawPrefs ? json_decode((string) $rawPrefs, true) : [];
        $preferences = array_merge([
            'layout' => 'side',
            'open_whatsapp' => 'app',
            'whatsapp_sharing_preference' => 'form',
            'hide_floating_button' => false,
            'hide_floating_contact' => false,
        ], is_array($savedPrefs) ? $savedPrefs : []);

        // Sign-in history from sessions table
        $signInHistory = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->take(20)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->values()
            ->map(function ($session, $index) {
                $carbon = Carbon::createFromTimestamp($session->last_activity);

                return [
                    'id' => $session->id,
                    'index' => $index + 1,
                    'ip_address' => $session->ip_address ?: '127.0.0.1',
                    'user_agent' => $session->user_agent,
                    'device' => $this->parseDevice($session->user_agent),
                    'sign_in_date' => $carbon->toIso8601String(),
                    'formatted_date' => $carbon->translatedFormat('Y-m-d h:i A'),
                    'diff_for_humans' => $carbon->diffForHumans(),
                ];
            });

        return Inertia::render('App/Profile/Index', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'isEnabled' => $isEnabled,
            'isEnforced' => $tenant?->two_factor_enforcement === 'required',
            'currentMethod' => $user->two_factor_type,
            'smsEnabled' => $this->twoFactor->isSmsEnabled($user),
            'recoveryCodes' => $isEnabled && $user->two_factor_recovery_codes
                ? json_decode(decrypt($user->two_factor_recovery_codes), true)
                : null,
            'employeeDetails' => [
                'job_title' => $jobTitle,
                'department' => $department,
                'employee_number' => $employee?->employee_number,
            ],
            'roles' => $roles,
            'isFullAccess' => $isFullAccess,
            'permissions' => $permissions,
            'preferences' => $preferences,
            'signInHistory' => $signInHistory,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $request->user()->fill($request->only(['name', 'email']));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return back()->with('success', __('profile.updated_success') ?? 'تم تحديث الملف الشخصي بنجاح');
    }

    /**
     * Update the user's preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layout' => ['sometimes', 'required', 'string', 'in:side,top'],
            'open_whatsapp' => ['sometimes', 'required', 'string', 'in:app,web'],
            'whatsapp_sharing_preference' => ['sometimes', 'required', 'string', 'in:form,direct'],
            'hide_floating_button' => ['sometimes', 'boolean'],
            'hide_floating_contact' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        if ($user->tenant_id) {
            $existing = [];
            $currentSetting = Setting::forTenant($user->tenant_id, 'user_preferences.'.$user->id);

            if ($currentSetting) {
                $decoded = json_decode((string) $currentSetting, true);

                if (is_array($decoded)) {
                    $existing = $decoded;
                }
            }

            $merged = array_merge([
                'layout' => 'side',
                'open_whatsapp' => 'app',
                'whatsapp_sharing_preference' => 'form',
                'hide_floating_button' => false,
                'hide_floating_contact' => false,
            ], $existing, $validated);

            Setting::setForTenant(
                $user->tenant_id,
                'user_preferences.'.$user->id,
                json_encode($merged),
                'user_preferences'
            );
        }

        return back()->with('success', __('profile.preferences.saved_success') ?? 'تم حفظ التفضيلات بنجاح');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->can_update_photo) {
            return back()->with('error', 'لا يمكن تعديل الصورة الشخصية للموظفين الذين لديهم صورة في ملف الموارد البشرية');
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'extensions:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['photo_path' => $path]);
        }

        return back()->with('success', __('profile.photo.success') ?? 'تم تحديث الصورة الشخصية بنجاح');
    }

    /**
     * Parse device and browser string from user agent.
     */
    protected function parseDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'غير معروف';
        }

        $browser = 'متصفح';

        if (str_contains($userAgent, 'Edg')) {
            $browser = 'Edge';
        } elseif (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            $browser = 'Opera';
        }

        $platform = 'كمبيوتر';

        if (str_contains($userAgent, 'iPhone')) {
            $platform = 'iPhone';
        } elseif (str_contains($userAgent, 'iPad')) {
            $platform = 'iPad';
        } elseif (str_contains($userAgent, 'Android')) {
            $platform = 'Android';
        } elseif (str_contains($userAgent, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS')) {
            $platform = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $platform = 'Linux';
        }

        return "{$platform} ({$browser})";
    }
}
