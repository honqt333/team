<?php

declare(strict_types=1);

namespace App\Actions\CompanyProfile;

use App\Models\Tenant;
use App\Models\TenantAddress;
use App\Models\TenantTaxSetting;
use App\Models\TenantZatcaSetting;
use App\Support\CountryCurrencyRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateCompanyProfileSectionAction
{
    /**
     * Validate and execute updating a specific company profile section.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, string $section, array $data): void
    {
        $this->validateSection($section, $data, $tenant);

        match ($section) {
            'profile' => $this->updateProfile($tenant, $data),
            'contact' => $this->updateContact($tenant, $data),
            'address' => $this->updateAddress($tenant, $data),
            'vat' => $this->updateVat($tenant, $data),
            'zatca' => $this->updateZatca($tenant, $data),
            'numbering' => $this->updateNumbering($tenant, $data),
            'workflow' => $this->updateWorkflow($tenant, $data),
            'notifications' => $this->updateNotifications($tenant, $data),
            default => null,
        };
    }

    /**
     * Validate section data.
     *
     * @throws ValidationException
     */
    public function validateSection(string $section, array $data, Tenant $tenant): void
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
                    'country' => ['nullable', 'string', Rule::in(CountryCurrencyRegistry::countryCodes())],
                    'currency' => ['nullable', 'string', Rule::in(CountryCurrencyRegistry::currencyCodes())],
                    'timezone' => 'nullable|string|max:64',
                ];
                break;

            case 'contact':
                $rules = [
                    'phone' => 'nullable|string|max:30',
                    'email' => 'nullable|email|max:255',
                ];
                break;

            case 'address':
                $rules = [
                    'address_line' => 'nullable|string|max:255',
                    'street' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:100',
                    'district' => 'nullable|string|max:100',
                    'building_number' => 'nullable|string|max:20',
                    'postal_code' => 'nullable|string|max:10',
                    'country' => 'nullable|string|max:100',
                    'latitude' => 'nullable',
                    'longitude' => 'nullable',
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

                $country = CountryCurrencyRegistry::tenantCountry($tenant);
                $countryMeta = CountryCurrencyRegistry::countryMeta($country);

                // If VAT is enabled, validate VAT number according to tenant's country rules (ZATCA 3...3 rule for SA only)
                if ($data['vat_enabled'] ?? false) {
                    if ($country === 'SA') {
                        $rules['vat_number'] = ['required', 'string', 'size:15', 'regex:/^3[0-9]{13}3$/'];
                    } else {
                        $min = (int) ($countryMeta['vat_number_min'] ?? 5);
                        $max = (int) ($countryMeta['vat_number_max'] ?? 30);
                        $vatRules = ['required', 'string', "min:{$min}", "max:{$max}"];

                        if (! empty($countryMeta['vat_number_regex'])) {
                            $vatRules[] = 'regex:'.$countryMeta['vat_number_regex'];
                        }
                        $rules['vat_number'] = $vatRules;
                    }

                    $messages = [
                        'vat_number.required' => __('validation.required', ['attribute' => __('purchasing.tax_number')]),
                        'vat_number.size' => __('validation.tax_number_invalid'),
                        'vat_number.min' => __('validation.tax_number_invalid'),
                        'vat_number.max' => __('validation.tax_number_invalid'),
                        'vat_number.regex' => __('validation.tax_number_invalid'),
                    ];
                }
                break;

            case 'numbering':
                $formatValidator = function ($attribute, $value, $fail) {
                    if (strpos((string) $value, '{SEQ}') === false) {
                        $fail(__('company_profile.numbering.seq_required'));
                    }
                    $allowed = ['{SEQ}', '{YYYY}', '{YY}', '{MM}', '{DD}', '{CENTER}'];
                    $cleaned = (string) $value;

                    foreach ($allowed as $token) {
                        $cleaned = str_replace($token, '', $cleaned);
                    }

                    if (preg_match('/\{[^}]+\}/', $cleaned)) {
                        $fail(__('company_profile.numbering.invalid_tokens'));
                    }
                };

                $rules = [
                    'invoice_number_format' => ['required', 'string', 'max:100', $formatValidator],
                    'work_order_number_format' => ['nullable', 'string', 'max:100', $formatValidator],
                    'quote_number_format' => ['nullable', 'string', 'max:100', $formatValidator],
                ];
                break;

            case 'workflow':
                $rules = [
                    'allow_lower_odometer' => 'boolean',
                    'max_discount_percentage' => 'nullable|integer|min:0|max:100',
                    'require_phone_on_creation' => 'boolean',
                    'default_warranty_days' => 'nullable|integer|min:0|max:3650',
                ];
                break;

            case 'notifications':
                $rules = [
                    'work_order_created' => 'nullable|string|max:1000',
                    'work_order_completed' => 'nullable|string|max:1000',
                    'quote_shared' => 'nullable|string|max:1000',
                    'invoice_issued' => 'nullable|string|max:1000',
                ];
                break;
        }

        if (empty($rules)) {
            return;
        }

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }

    private function updateProfile(Tenant $tenant, array $data): void
    {
        $country = CountryCurrencyRegistry::normalizeCountry($data['country'] ?? $tenant->country);
        $currency = CountryCurrencyRegistry::normalizeCurrency($data['currency'] ?? $tenant->currency, $country);
        $countryMeta = CountryCurrencyRegistry::countryMeta($country);
        $timezone = $data['timezone'] ?? $tenant->timezone ?? $countryMeta['timezone'];

        $tenant->update([
            'legal_name' => $data['legal_name'] ?? $tenant->legal_name,
            'legal_name_en' => $data['legal_name_en'] ?? null,
            'trade_name' => $data['trade_name'] ?? null,
            'owner_name' => $data['owner_name'] ?? null,
            'vat_number' => array_key_exists('vat_number', $data) ? $data['vat_number'] : $tenant->vat_number,
            'cr_number' => $data['cr_number'] ?? null,
            'iban' => $data['iban'] ?? null,
            'country' => $country,
            'currency' => $currency,
            'timezone' => $timezone,
        ]);

        // Keep TenantTaxSetting currency_code in sync with the tenant's operating currency
        TenantTaxSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['currency_code' => $currency]
        );
    }

    private function updateContact(Tenant $tenant, array $data): void
    {
        $tenant->update([
            'phone' => array_key_exists('phone', $data) ? ($data['phone'] ?: null) : $tenant->phone,
            'email' => array_key_exists('email', $data) ? ($data['email'] ?: null) : $tenant->email,
        ]);
    }

    private function updateAddress(Tenant $tenant, array $data): void
    {
        TenantAddress::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'address_line' => $data['address_line'] ?? null,
                'street' => $data['street'] ?? null,
                'city' => $data['city'] ?? null,
                'district' => $data['district'] ?? null,
                'building_number' => $data['building_number'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]
        );
    }

    private function updateVat(Tenant $tenant, array $data): void
    {
        $country = CountryCurrencyRegistry::tenantCountry($tenant);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);
        $defaultRate = CountryCurrencyRegistry::defaultVatRate($country);

        if (CountryCurrencyRegistry::isVatRateLocked($country)) {
            $servicesRate = $defaultRate;
            $partsRate = $defaultRate;
        } else {
            $servicesRate = (float) ($data['services_vat_rate'] ?? $defaultRate);
            $partsRate = (float) ($data['parts_vat_rate'] ?? $servicesRate);
        }

        $payload = [
            'vat_enabled' => $data['vat_enabled'] ?? false,
            'vat_rate' => $servicesRate,
            'services_vat_rate' => $servicesRate,
            'parts_vat_rate' => $partsRate,
            'services_inclusive' => $data['services_inclusive'] ?? false,
            'parts_inclusive' => $data['parts_inclusive'] ?? false,
            'show_amount_before_vat' => $data['show_amount_before_vat'] ?? true,
            'currency_code' => $currency,
        ];

        if (array_key_exists('vat_number', $data)) {
            $payload['tax_number'] = $data['vat_number'];
        }

        TenantTaxSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $payload
        );

        // Update VAT number on tenant profile if provided
        if (isset($data['vat_number'])) {
            $tenant->update(['vat_number' => $data['vat_number']]);
        }
    }

    private function updateZatca(Tenant $tenant, array $data): void
    {
        TenantZatcaSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['qr_enabled' => $data['qr_enabled'] ?? false]
        );
    }

    private function updateNumbering(Tenant $tenant, array $data): void
    {
        $tenant->update([
            'invoice_number_format' => $data['invoice_number_format'] ?? 'INV-{CENTER}-{YYYY}-{SEQ}',
            'work_order_number_format' => $data['work_order_number_format'] ?? 'WO-{SEQ}',
            'quote_number_format' => $data['quote_number_format'] ?? 'QT-{SEQ}',
        ]);
    }

    private function updateWorkflow(Tenant $tenant, array $data): void
    {
        $tenant->update([
            'workflow_settings' => [
                'allow_lower_odometer' => (bool) ($data['allow_lower_odometer'] ?? false),
                'max_discount_percentage' => (int) ($data['max_discount_percentage'] ?? 15),
                'require_phone_on_creation' => (bool) ($data['require_phone_on_creation'] ?? true),
                'default_warranty_days' => (int) ($data['default_warranty_days'] ?? 30),
            ],
        ]);
    }

    private function updateNotifications(Tenant $tenant, array $data): void
    {
        $tenant->update([
            'notification_templates' => [
                'work_order_created' => $data['work_order_created'] ?? '',
                'work_order_completed' => $data['work_order_completed'] ?? '',
                'quote_shared' => $data['quote_shared'] ?? '',
                'invoice_issued' => $data['invoice_issued'] ?? '',
            ],
        ]);
    }
}
