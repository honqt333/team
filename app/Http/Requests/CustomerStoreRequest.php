<?php

namespace App\Http\Requests;

use App\Support\TenancyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();

        return [
            'type' => ['required', 'string', Rule::in(['individual', 'company', 'government', 'vip'])],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255', 'required_if:type,company', 'required_if:type,government'],
            'phone' => [
                'required',
                'string',
                'max:30',
                'regex:/^\+?966\d{9}$/',
                Rule::unique('customers')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('center_id', $centerId)),
            ],
            'whatsapp' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^\+?966\d{9}$/',
            ],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'notes' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'regex:/^\d{15}$/'],
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
            'phone.regex' => __('validation.phone_regex_error'),
            'whatsapp.regex' => __('validation.phone_regex_error'),
            'tax_number.regex' => __('validation.tax_number_invalid'),
        ];
    }
}
