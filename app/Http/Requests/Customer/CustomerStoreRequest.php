<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\TenantAware;
use App\Support\CountryCurrencyRegistry;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('crm.customers.create') || $this->user()->can('customers.create');
    }

    protected function prepareForValidation(): void
    {
        $country = $this->user()?->tenant?->country;

        if ($this->phone) {
            $this->merge([
                'phone' => PhoneNumber::normalize($this->phone, $country),
            ]);
        }

        if ($this->whatsapp) {
            $this->merge([
                'whatsapp' => PhoneNumber::normalize($this->whatsapp, $country),
            ]);
        }
    }

    public function rules(): array
    {
        $tenantCountry = PhoneNumber::resolveCountry($this->user()?->tenant?->country);
        $taxRule = CountryCurrencyRegistry::isZatcaApplicable($tenantCountry)
            ? ['nullable', 'string', 'regex:/^\d{15}$/']
            : ['nullable', 'string', 'max:50'];

        return [
            'type' => ['required', Rule::in(['individual', 'company', 'government'])],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required_if:type,company', 'nullable', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:25',
                Rule::unique('customers', 'phone')
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at'),
            ],
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => $taxRule,
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'building_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_name.required_if' => 'اسم جهة الاتصال مطلوب للعملاء من نوع شركة.',
            'tax_number.regex' => 'الرقم الضريبي يجب أن يكون 15 رقماً.',
        ];
    }
}
