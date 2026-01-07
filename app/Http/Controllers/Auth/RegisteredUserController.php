<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
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
     * @throws \Illuminate\Validation\ValidationException
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
        if (\App\Models\Tenant::where('phone', $formattedPhone)->exists()) {
            return back()->withErrors(['phone' => __('auth.phone_already_registered')]);
        }

        // Check phone verification if Authentica is enabled
        if (\App\Http\Controllers\Auth\PhoneVerificationController::isEnabled()) {
            if (!session('phone_verified') || session('verified_phone_number') !== $this->formatPhoneNumber($request->phone)) {
                return back()->withErrors(['phone' => __('auth.verify_phone_first')]);
            }
        }

        $user = DB::transaction(function () use ($request) {
            $ownerName = $request->first_name . ' ' . $request->last_name;
            $phone = '+966' . ltrim($request->phone, '0');
            
            // 1. Create Tenant with full company profile
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name) . '-' . Str::random(6),
                // Company Profile - all same as company_name initially
                'legal_name' => $request->company_name,
                'legal_name_en' => $request->company_name,
                'trade_name' => $request->company_name,
                'owner_name' => $ownerName,
                'phone' => $phone,
                'email' => $request->email,
                // Logo will be null initially, system will use default logo
            ]);

            // 2. Create Default Center (Main Branch) with same data
            $center = Center::create([
                'tenant_id' => $tenant->id,
                'name' => $request->company_name,
                'name_ar' => $request->company_name,
                'name_en' => $request->company_name,
                'slug' => 'main-' . $tenant->id,
                'is_active' => true,
                'is_main' => true, // First center is always main
                'center_type' => 'المركز الرئيسي',
                'manager_name' => $ownerName,
                'phone' => $phone,
                'email' => $request->email,
            ]);

            // 3. Create User (Super Admin)
            $user = User::create([
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => '+966' . ltrim($request->phone, '0'),
                'password' => Hash::make($request->password),
            ]);

            // 4. Attach User to Center
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

            // 5. Assign Super Admin role (roles are seeded in Tenant::booted)
            $user->assignRole('super_admin');

            // 6. Store promo code for future use (optional)
            if ($request->promo_code) {
                // TODO: Validate and apply promo code
                // PromoCode::apply($tenant, $request->promo_code);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        // Clear phone verification session
        session()->forget(['phone_verified', 'verified_phone_number', 'phone_verification_number']);

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
            $phone = '+966' . substr($phone, 1);
        }
        // If starts with 5 (Saudi mobile), add +966
        elseif (str_starts_with($phone, '5')) {
            $phone = '+966' . $phone;
        }
        // If starts with 966, add +
        elseif (str_starts_with($phone, '966')) {
            $phone = '+' . $phone;
        }
        // If doesn't start with +, add it
        elseif (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
