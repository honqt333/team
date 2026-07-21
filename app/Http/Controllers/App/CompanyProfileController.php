<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\CompanyTransaction;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Tenant;
use App\Models\TenantAddress;
use App\Models\TenantTaxSetting;
use App\Models\TenantZatcaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
                'logo_url' => $tenant->logo_path ? Storage::url($tenant->logo_path) : asset('images/logo.png'),
                'country' => 'SA', // Locked
                'currency' => 'SAR', // Locked
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
            ],
            'numbering' => [
                'invoice_number_format' => $tenant->invoice_number_format ?? 'INV-{CENTER}-{YYYY}-{SEQ}',
            ],
            'admin_user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'email' => $adminUser->email,
            ],
            'branches' => $tenant->centers()->select('id', 'name', 'slug', 'is_active', 'created_at')->get(),
            'company_transactions' => CompanyTransaction::with(['incomeCategory', 'approvedBy', 'updatedBy'])
                ->whereNull('center_id')
                ->latest('transaction_date')
                ->get(),
            'income_categories' => IncomeCategory::active()
                ->get()
                ->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'transaction_type' => $cat->transaction_type,
                ]),
            'company_invoices' => [
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
                    $trialStarts = $tenant->created_at;
                    $trialEnds = $tenant->trial_ends_at ?: $trialStarts->copy()->addDays(14);
                    $trialDays = (int) round($trialStarts->diffInDays($trialEnds) ?: 14);
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
                    ->whereIn('status', ['active', 'trial', 'trialing'])
                    ->with('plan')
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
            'available_plans' => Plan::where('is_active', true)
                ->orderBy('price_monthly', 'asc')
                ->get(),
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

        // Delete old logo if exists
        if ($tenant->logo_path && Storage::exists($tenant->logo_path)) {
            Storage::delete($tenant->logo_path);
        }

        // Store new logo
        $file = $request->file('logo');
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            "tenants/{$tenant->id}",
            "logo.{$extension}",
            'public'
        );

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
    public function update(Request $request)
    {
        $request->validate([
            'section' => 'required|in:profile,contact,address,vat,zatca,numbering',
            'data' => 'required|array',
        ]);

        $section = $request->input('section');
        $data = $request->input('data');
        $tenant = auth()->user()->tenant;

        $errors = $this->validateSection($section, $data, $tenant);

        if ($errors) {
            return back()->withErrors($errors);
        }

        switch ($section) {
            case 'profile':
                $tenant->update([
                    'legal_name' => $data['legal_name'] ?? $tenant->legal_name,
                    'legal_name_en' => $data['legal_name_en'] ?? null,
                    'trade_name' => $data['trade_name'] ?? null,
                    'owner_name' => $data['owner_name'] ?? null,
                    'vat_number' => $data['vat_number'] ?? null,
                    'cr_number' => $data['cr_number'] ?? null,
                    'iban' => $data['iban'] ?? null,
                ]);
                break;

            case 'contact':
                $tenant->update([
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                ]);
                break;

            case 'address':
                TenantAddress::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    [
                        'address_line' => $data['address_line'] ?? null,
                        'street' => $data['street'] ?? null,
                        'city' => $data['city'] ?? null,
                        'district' => $data['district'] ?? null,
                        'building_number' => $data['building_number'] ?? null,
                        'postal_code' => $data['postal_code'] ?? null,
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                    ]
                );
                break;

            case 'vat':
                // Enforce 15% VAT rate for Saudi Arabia ('SA')
                $servicesVatRate = $data['services_vat_rate'] ?? 15;
                $partsVatRate = $data['parts_vat_rate'] ?? 15;

                // Currently SA is the only supported country, so we lock to 15
                $servicesVatRate = 15;
                $partsVatRate = 15;

                TenantTaxSetting::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    [
                        'vat_enabled' => $data['vat_enabled'] ?? false,
                        'services_vat_rate' => $servicesVatRate,
                        'parts_vat_rate' => $partsVatRate,
                        'services_inclusive' => $data['services_inclusive'] ?? false,
                        'parts_inclusive' => $data['parts_inclusive'] ?? false,
                        'show_amount_before_vat' => $data['show_amount_before_vat'] ?? true,
                    ]
                );

                // Update VAT number on tenant profile if provided
                if (isset($data['vat_number'])) {
                    $tenant->update(['vat_number' => $data['vat_number']]);
                }
                break;

            case 'zatca':
                TenantZatcaSetting::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    ['qr_enabled' => $data['qr_enabled'] ?? false]
                );
                break;

            case 'numbering':
                $tenant->update([
                    'invoice_number_format' => $data['invoice_number_format'] ?? 'INV-{CENTER}-{YYYY}-{SEQ}',
                ]);
                break;
        }

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
     * Validate section data.
     */
    private function validateSection(string $section, array $data, Tenant $tenant): ?array
    {
        $rules = [];
        $messages = [];

        switch ($section) {
            case 'profile':
                $rules = [
                    'legal_name' => 'required|string|max:255',
                    'legal_name_en' => 'required|string|max:255',
                    'trade_name' => 'required|string|max:255',
                    'owner_name' => 'required|string|max:255',
                    'cr_number' => 'nullable|string|max:20',
                    'iban' => 'nullable|string|max:34',
                ];
                break;

            case 'vat':
                $rules = [
                    'vat_enabled' => 'required|boolean',
                    'services_vat_rate' => 'required_if:vat_enabled,true|numeric|min:0|max:100',
                    'parts_vat_rate' => 'required_if:vat_enabled,true|numeric|min:0|max:100',
                    'services_inclusive' => 'boolean',
                    'parts_inclusive' => 'boolean',
                    'show_amount_before_vat' => 'boolean',
                    'vat_number' => 'nullable|string',
                ];

                // If VAT is enabled, VAT number is required and must be exactly 15 digits starting and ending with 3 (ZATCA Rule)
                if ($data['vat_enabled'] ?? false) {
                    $rules['vat_number'] = ['required', 'string', 'size:15', 'regex:/^3[0-9]{13}3$/'];
                    $messages = [
                        'vat_number.required' => __('validation.required', ['attribute' => __('purchasing.tax_number')]),
                        'vat_number.size' => __('validation.tax_number_invalid'),
                        'vat_number.regex' => __('validation.tax_number_invalid'),
                    ];
                }
                break;

            case 'numbering':
                $rules = [
                    'invoice_number_format' => [
                        'required',
                        'string',
                        'max:100',
                        function ($attribute, $value, $fail) {
                            if (strpos($value, '{SEQ}') === false) {
                                $fail(__('company_profile.numbering.seq_required'));
                            }
                            // Only allow specific tokens
                            $allowed = ['{SEQ}', '{YYYY}', '{YY}', '{MM}', '{DD}', '{CENTER}'];
                            $cleaned = $value;

                            foreach ($allowed as $token) {
                                $cleaned = str_replace($token, '', $cleaned);
                            }

                            // Check for remaining curly braces (invalid tokens)
                            if (preg_match('/\{[^}]+\}/', $cleaned)) {
                                $fail(__('company_profile.numbering.invalid_tokens'));
                            }
                        },
                    ],
                ];
                break;
        }

        if (empty($rules)) {
            return null;
        }

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return null;
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
