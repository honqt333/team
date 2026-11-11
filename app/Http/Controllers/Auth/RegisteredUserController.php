<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\PromoCodeValidator;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected PromoCodeValidator $promoValidator
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'phone_verification_enabled' => PhoneVerificationController::isEnabled(),
            'phone_verified' => session('phone_verified', false),
            'verified_phone' => session('verified_phone_number', ''),
        ]);
    }

    /**
     * Handle an incoming registration request.
     * Creates: Tenant → Center → User (with Super Admin role)
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'promo_code' => 'nullable|string|max:50',
            'terms' => 'required|accepted',
        ]);

        // Check if phone already exists for any tenant
        $formattedPhone = $this->formatPhoneNumber($request->phone);

        if (Tenant::where('phone', $formattedPhone)->exists()) {
            return back()->withErrors(['phone' => __('auth.phone_already_registered')]);
        }

        // Check phone verification if Authentica is enabled
        if (PhoneVerificationController::isEnabled()) {
            if (! session('phone_verified') || session('verified_phone_number') !== $this->formatPhoneNumber($request->phone)) {
                return back()->withErrors(['phone' => __('auth.verify_phone_first')]);
            }
        }

        // The promo code is checked BEFORE anything is created: a code
        // that cannot be used must not leave a half-registered tenant
        // behind, and the customer must be told instead of believing a
        // discount was applied.
        $promoCheck = null;

        if ($request->filled('promo_code')) {
            $promoCheck = $this->promoValidator->check((string) $request->input('promo_code'));

            if (! $promoCheck->isValid()) {
                throw ValidationException::withMessages([
                    'promo_code' => $this->promoValidator->messageFor($promoCheck),
                ]);
            }
        }

        // A code that gives trial DAYS is the one kind that can be
        // applied to a free trial. A code that discounts a paid plan
        // has nothing to discount here — it stays valid and is used
        // when the tenant subscribes.
        $extraTrialDays = 0;

        if ($promoCheck?->isValid() && $promoCheck->promo->discount_type === 'trial_days') {
            $extraTrialDays = (int) $promoCheck->promo->discount_value;
        }

        $trialPlan = Plan::where('slug', 'trial')->first();
        $baseTrialDays = (int) ($trialPlan?->trial_days ?: app(SubscriptionLifecycleService::class)->getDefaultTrialDays());
        $trialDays = $baseTrialDays + $extraTrialDays;

        $user = DB::transaction(function () use ($request, $promoCheck, $extraTrialDays, $trialDays, $trialPlan) {
            $ownerName = $request->first_name.' '.$request->last_name;
            $phone = '+966'.ltrim($request->phone, '0');

            // 1. Create Tenant with full company profile
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name).'-'.Str::random(6),
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                // Company Profile - all same as company_name initially
                'legal_name' => $request->company_name,
                'legal_name_en' => $request->company_name,
                'trade_name' => $request->company_name,
                'owner_name' => $ownerName,
                'phone' => $phone,
                'email' => $request->email,
            ]);

            // Create default trial subscription in database using the Trial Plan
            if ($trialPlan) {
                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $trialPlan->id,
                    'status' => 'trialing',
                    'billing_cycle' => 'yearly',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($trialDays),
                    'trial_ends_at' => now()->addDays($trialDays),
                    'price' => 0.00,
                    'promo_code' => $promoCheck?->promo?->code,
                    'auto_renew' => false,
                ]);

                if ($promoCheck?->isValid() && $extraTrialDays > 0) {
                    // The use of the code is recorded, so its limits
                    // (how many times it may be used) count.
                    $this->promoValidator->recordUsage(
                        $promoCheck->promo,
                        $tenant->id,
                        $subscription->id
                    );
                }
            }

            // 2. Create Default Center (Main Branch) with same data
            $center = Center::create([
                'tenant_id' => $tenant->id,
                'name' => $request->company_name,
                'name_ar' => $request->company_name,
                'name_en' => $request->company_name,
                'slug' => 'main-'.$tenant->id,
                'is_active' => true,
                'is_main' => true, // First center is always main
                'center_type' => 'المركز الرئيسي',
                'manager_name' => $ownerName,
                'phone' => $phone,
                'email' => $request->email,
            ]);

            // Set Spatie permissions team context for the new tenant
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            // 3. Create User (Super Admin)
            $user = User::create([
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'name' => $request->first_name.' '.$request->last_name,
                'email' => $request->email,
                'phone' => '+966'.ltrim($request->phone, '0'),
                'password' => Hash::make($request->password),
            ]);

            // 4. Attach User to Center
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

            // 5. Assign Super Admin role (roles are seeded in Tenant::booted)
            $user->assignRole('super_admin');

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        // Clear phone verification session
        session()->forget(['phone_verified', 'verified_phone_number', 'phone_verification_number']);

        if ($promoCheck?->isValid()) {
            $message = $extraTrialDays > 0
                ? __('company_profile.subscription.promo_trial_days_applied', ['days' => $extraTrialDays])
                : __('company_profile.subscription.promo_valid_for_plan');

            return redirect(route('dashboard', absolute: false))->with('success', $message);
        }

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Format phone number to international format.
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces or dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // If starts with 0, remove it and add +966
        if (str_starts_with($phone, '0')) {
            $phone = '+966'.substr($phone, 1);
        }
        // If starts with 5 (Saudi mobile), add +966
        elseif (str_starts_with($phone, '5')) {
            $phone = '+966'.$phone;
        }
        // If starts with 966, add +
        elseif (str_starts_with($phone, '966')) {
            $phone = '+'.$phone;
        }
        // If doesn't start with +, add it
        elseif (! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }
}
