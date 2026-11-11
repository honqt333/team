<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\CountryCurrencyRegistry;
use App\Support\PhoneNumber;
use App\Support\TenancyContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $country = TenancyContext::user()?->tenant?->country;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $customerId = $this->route('customer')?->id;
        $tenantCountry = PhoneNumber::resolveCountry(TenancyContext::user()?->tenant?->country);
        $taxRule = CountryCurrencyRegistry::isZatcaApplicable($tenantCountry)
            ? ['nullable', 'string', 'regex:/^\d{15}$/']
            : ['nullable', 'string', 'max:50'];

        return [
            'type' => ['sometimes', 'string', Rule::in(['individual', 'company', 'government'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_name' => [
                Rule::requiredIf(function () {
                    $type = $this->type ?? $this->route('customer')?->type;

                    return in_array($type, ['company', 'government']);
                }),
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'sometimes',
                'string',
                'max:30',
                'regex:/^\+[1-9]\d{6,14}$/',
                Rule::unique('customers')
                    ->ignore($customerId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('center_id', $centerId)),
            ],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+[1-9]\d{6,14}$/'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'notes' => ['nullable', 'string'],
            'tax_number' => $taxRule,
            'address_line' => ['nullable', 'string', 'max:500'],
            'building_number' => ['nullable', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'contact_name.required_if' => __('validation.required', ['attribute' => __('validation.attributes.contact_name')]),
            'phone.unique' => __('validation.unique', ['attribute' => __('validation.attributes.phone')]),
            'tax_number.regex' => __('validation.tax_number_invalid'),
        ];
    }
}
