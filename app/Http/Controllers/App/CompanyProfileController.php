<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\CompanyProfile\UpdateCompanyProfileSectionAction;
use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\CompanyTransaction;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use App\Models\PurchaseInvoice;
use App\Models\Tenant;
use App\Services\Payment\PaymentManager;
use App\Services\Zatca\ZatcaOnboardingService;
use App\Support\Media\ImageType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class CompanyProfileController extends Controller
{
    /**
     * Display the company profile settings page.
     */
    public function index(): Response
    {
        $tenant = auth()->user()->tenant;
        $tenant->load(['taxSettings', 'address', 'zatcaSettings']);

        // Get the admin user (first user created for this tenant, usually the owner)
        $adminUser = auth()->user();

        return Inertia::render('Settings/Company/Index', [
            'profile' => [
                'legal_name' => $tenant->legal_name,
                'legal_name_en' => $tenant->legal_name_en,
                'trade_name' => $tenant->trade_name,
                'owner_name' => $tenant->owner_name,
                'vat_number' => $tenant->vat_number,
                'cr_number' => $tenant->cr_number,
                'iban' => $tenant->iban,
                'logo_url' => $tenant->logo_path ? Storage::url($tenant->logo_path) : asset('images/brand/logo-256.png'),
                'country' => $tenant->country ?? config('app.default_country', 'SA'),
                'currency' => $tenant->currency ?? config('app.default_currency', 'SAR'),
            ],
            'contact' => [
                'phone' => $tenant->phone,
                'email' => $tenant->email,
            ],
            'address' => $tenant->address ? [
                'address_line' => $tenant->address->address_line,
                'street' => $tenant->address->street,
                'city' => $tenant->address->city,
                'district' => $tenant->address->district,
                'building_number' => $tenant->address->building_number,
                'postal_code' => $tenant->address->postal_code,
                'latitude' => $tenant->address->latitude,
                'longitude' => $tenant->address->longitude,
            ] : null,
            'vat' => $tenant->taxSettings ? [
                'vat_enabled' => $tenant->taxSettings->vat_enabled,
                'services_vat_rate' => $tenant->taxSettings->services_vat_rate ?? 15,
                'parts_vat_rate' => $tenant->taxSettings->parts_vat_rate ?? 15,
                'services_inclusive' => $tenant->taxSettings->services_inclusive ?? false,
                'parts_inclusive' => $tenant->taxSettings->parts_inclusive ?? false,
                'show_amount_before_vat' => $tenant->taxSettings->show_amount_before_vat ?? true,
            ] : [
                'vat_enabled' => false,
                'services_vat_rate' => 15,
                'parts_vat_rate' => 15,
                'services_inclusive' => false,
                'parts_inclusive' => false,
                'show_amount_before_vat' => true,
            ],
            'zatca' => [
                'qr_enabled' => $tenant->zatcaSettings?->qr_enabled ?? false,
                'is_phase2_active' => $tenant->zatcaSettings?->isPhase2Active() ?? false,
                'onboarding_status' => $tenant->zatcaSettings?->onboarding_status ?? 'not_started',
                'environment' => $tenant->zatcaSettings?->environment ?? 'simulation',
                'production_csid_expires_at' => $tenant->zatcaSettings?->production_csid_expires_at?->format('Y-m-d'),
                'is_eligible_for_phase2' => app(ZatcaOnboardingService::class)->isEligibleForPhase2($tenant),
                'eligibility_errors' => app(ZatcaOnboardingService::class)->validatePrerequisites($tenant),
            ],
            'numbering' => [
                'invoice_number_format' => $tenant->invoice_number_format ?? 'INV-{CENTER}-{YYYY}-{SEQ}',
                'work_order_number_format' => $tenant->work_order_number_format ?? 'WO-{SEQ}',
                'quote_number_format' => $tenant->quote_number_format ?? 'QT-{SEQ}',
            ],
            'workflow' => [
                'allow_lower_odometer' => (bool) $tenant->getWorkflowSetting('allow_lower_odometer', false),
                'max_discount_percentage' => (int) $tenant->getWorkflowSetting('max_discount_percentage', 15),
                'require_phone_on_creation' => (bool) $tenant->getWorkflowSetting('require_phone_on_creation', true),
                'default_warranty_days' => (int) $tenant->getWorkflowSetting('default_warranty_days', 30),
            ],
            'notifications' => [
                'work_order_created' => $tenant->getNotificationTemplate('work_order_created', __('company_profile.notification_templates.work_order_created')),
                'work_order_completed' => $tenant->getNotificationTemplate('work_order_completed', __('company_profile.notification_templates.work_order_completed')),
                'quote_shared' => $tenant->getNotificationTemplate('quote_shared', __('company_profile.notification_templates.quote_shared')),
                'invoice_issued' => $tenant->getNotificationTemplate('invoice_issued', __('company_profile.notification_templates.invoice_issued')),
            ],
            'admin_user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'email' => $adminUser->email,
            ],
            'branches' => $tenant->centers()->select('id', 'name', 'slug', 'is_active', 'created_at')->get(),
            'company_transactions' => fn () => CompanyTransaction::with(['incomeCategory', 'approvedBy', 'updatedBy'])
                ->whereNull('center_id')
                ->latest('transaction_date')
                ->get(),
            'income_categories' => fn () => IncomeCategory::active()
                ->get()
                ->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'transaction_type' => $cat->transaction_type,
                ]),
            'company_invoices' => fn () => [
                'sales' => Invoice::withoutGlobalScope('center_scoped')
                    ->whereNull('center_id')
                    ->whereIn('id', function ($query) {
                        $query->select('invoice_id')->from('company_transactions')->whereNull('center_id')->whereNotNull('invoice_id');
                    })->with(['customer', 'center'])->latest()->get(),
                'purchases' => PurchaseInvoice::whereNull('center_id')
                    ->whereIn('id', function ($query) {
                        $query->select('purchase_invoice_id')->from('company_transactions')->whereNull('center_id')->whereNotNull('purchase_invoice_id');
                    })->with(['supplier', 'center'])->latest()->get(),
            ],
            'subscriptions' => rescue(function () use ($tenant) {
                $subs = $tenant->subscriptions()
                    ->with('plan')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $hasTrial = $subs->contains(function ($sub) {
                    return $sub->status === 'trialing' || $sub->status === 'trial';
                });

                if (! $hasTrial && $tenant->created_at) {
                    $defaultDays = (int) config('app.trial_days', 14);
                    $trialStarts = $tenant->created_at;
                    $trialEnds = $tenant->trial_ends_at ?: $trialStarts->copy()->addDays($defaultDays);
                    $trialDays = (int) round($trialStarts->diffInDays($trialEnds) ?: $defaultDays);
                    $trialStatus = $trialEnds->isFuture() ? 'trial' : 'expired';

                    $virtualTrial = [
                        'id' => 'trial-virtual',
                        'created_at' => $trialStarts->toIso8601String(),
                        'starts_at' => $trialStarts->toIso8601String(),
                        'ends_at' => $trialEnds->toIso8601String(),
                        'status' => $trialStatus,
                        'billing_cycle' => 'yearly',
                        'price' => 0.00,
                        'discount_amount' => 0.00,
                        'plan' => [
                            'name_ar' => "فترة تجريبية {$trialDays} يوم",
                            'name_en' => "{$trialDays} days Trial",
                            'price_monthly' => 0,
                            'price_yearly' => 0,
                        ],
                    ];

                    $subs = $subs->concat([$virtualTrial]);
                }

                return $subs;
            }, function () {
                return [];
            }),
            'current_subscription' => rescue(function () use ($tenant) {
                $sub = $tenant->subscriptions()
                    ->whereIn('status', ['active', 'trial', 'trialing', 'pending', 'pending_payment'])
                    ->with(['plan', 'receipts'])
                    ->orderByRaw("CASE WHEN status = 'pending_payment' THEN 1 WHEN status = 'pending' THEN 2 WHEN status = 'active' THEN 3 ELSE 4 END")
                    ->latest()
                    ->first();

                if (! $sub && ($tenant->status === 'trial' || $tenant->trial_ends_at)) {
                    $trialStarts = $tenant->created_at;
                    $trialEnds = $tenant->trial_ends_at ?: $trialStarts->copy()->addDays(14);
                    $trialDays = (int) round($trialStarts->diffInDays($trialEnds) ?: 14);
                    $trialStatus = $trialEnds->isFuture() ? 'trial' : 'expired';

                    $sub = [
                        'id' => 'trial-virtual',
                        'created_at' => $trialStarts->toIso8601String(),
                        'starts_at' => $trialStarts->toIso8601String(),
                        'ends_at' => $trialEnds->toIso8601String(),
                        'status' => $trialStatus,
                        'billing_cycle' => 'yearly',
                        'price' => 0.00,
                        'discount_amount' => 0.00,
                        'plan' => [
                            'name_ar' => "فترة تجريبية {$trialDays} يوم",
                            'name_en' => "{$trialDays} days Trial",
                            'price_monthly' => 0,
                            'price_yearly' => 0,
                            'features' => [],
                            'features_ar' => [],
                            'features_en' => [],
                        ],
                    ];
                }

                return $sub;
            }),
            'available_plans' => fn () => Plan::where('is_active', true)
                ->orderBy('price_monthly', 'asc')
                ->get(),
            'enabled_gateways' => rescue(function () {
                $paymentSettings = PaymentSettings::first() ?? new PaymentSettings;
                $manager = app(PaymentManager::class);
                $available = $manager->getAvailableGateways();

                return collect($available)->filter(function ($g) use ($paymentSettings) {
                    if ($g['name'] === 'bank_transfer') {
                        return (bool) ($paymentSettings->bank_transfer_enabled ?? false);
                    }
                    $config = match ($g['name']) {
                        'moyasar' => $paymentSettings->moyasar,
                        'tap' => $paymentSettings->tap,
                        'paytabs' => $paymentSettings->paytabs,
                        'tabby' => $paymentSettings->tabby,
                        'tamara' => $paymentSettings->tamara,
                        default => null,
                    };

                    return $config && ($config['enabled'] ?? false);
                })->values()->all();
            }, function () {
                return [];
            }),
            'payment_settings' => rescue(function () {
                return (PaymentSettings::first() ?? new PaymentSettings)->checkoutSettings();
            }, []),
            'bank_accounts' => rescue(function () {
                return PaymentSettings::first()?->bank_accounts ?? [];
            }),
        ]);
    }

    /**
     * Upload company logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $tenant = auth()->user()->tenant;

        // The extension of an uploaded file is chosen by the client, so
        // it is never used: the type is read from the bytes and the
        // extension written to disk comes from that. The file is served
        // from the public disk, so only inert raster types are accepted.
        $file = $request->file('logo');
        $binary = (string) file_get_contents($file->getRealPath());
        $extension = ImageType::extensionFor($binary);

        if ($extension === null) {
            throw ValidationException::withMessages([
                'logo' => __('company_profile.logo.error_type'),
            ]);
        }

        // Delete old logo only once the new one is known to be good — a
        // rejected upload must not leave the tenant without a logo.
        if ($tenant->logo_path && Storage::exists($tenant->logo_path)) {
            Storage::delete($tenant->logo_path);
        }

        $directory = "tenants/{$tenant->id}";
        $path = "{$directory}/logo.".time().'.'.$extension;

        Storage::disk('public')->put($path, $binary);

        $tenant->update(['logo_path' => $path]);

        return response()->json([
            'success' => true,
            'logo_url' => Storage::url($path),
            'message' => __('company_profile.logo.uploaded'),
        ]);
    }

    /**
     * Delete company logo.
     */
    public function deleteLogo()
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->logo_path && Storage::exists($tenant->logo_path)) {
            Storage::delete($tenant->logo_path);
        }

        $tenant->update(['logo_path' => null]);

        return response()->json([
            'success' => true,
            'message' => __('company_profile.logo.deleted'),
        ]);
    }

    /**
     * Update company settings by section.
     * Expects: { section: 'profile'|'contact'|'address'|'vat'|'zatca'|'numbering', data: {...} }
     */
    public function update(Request $request, UpdateCompanyProfileSectionAction $action)
    {
        $request->validate([
            'section' => 'required|in:profile,contact,address,vat,zatca,numbering,workflow,notifications',
            'data' => 'required|array',
        ]);

        $section = $request->input('section');
        $data = $request->input('data');
        $tenant = auth()->user()->tenant;

        $action->execute($tenant, $section, $data);

        // For VAT section, logout the user and redirect to login
        if ($section === 'vat') {
            $intendedUrl = route('settings.company', ['tab' => 'vat']);

            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            // Set intended URL in the NEW session after invalidation
            session()->put('url.intended', $intendedUrl);

            return redirect()->route('login')->with('success', __('common.vat_settings_updated'));
        }

        return back()->with('success', __('common.saved_success'));
    }

    /**
     * Update admin user email or password.
     */
    public function updateAdminUser(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'email' => 'sometimes|required|email|max:255|unique:users,email,'.$user->id,
            'current_password' => 'required_with:new_password|current_password',
            'new_password' => 'sometimes|required|min:8|confirmed',
        ], [
            'email.required' => __('common.validation.required', ['field' => __('company_profile.admin_user.email')]),
            'email.email' => __('company_profile.admin_user.email_invalid'),
            'email.unique' => __('company_profile.admin_user.email_taken'),
            'current_password.current_password' => __('company_profile.admin_user.current_password_wrong'),
            'new_password.min' => __('company_profile.admin_user.password_min'),
            'new_password.confirmed' => __('company_profile.admin_user.password_confirm_mismatch'),
        ]);

        // Update email if provided
        if ($request->has('email') && $request->email !== $user->email) {
            $user->email = $request->email;
        }

        // Update password if provided
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', __('common.saved_success'));
    }

    /**
     * Verify the current admin user's password.
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (Hash::check($request->password, auth()->user()->password)) {
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => __('company_profile.admin_user.current_password_wrong'),
        ], 422);
    }
}
