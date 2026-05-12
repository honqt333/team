<?php

namespace App\Http\Requests;

use App\Support\TenancyContext;
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $customerId = $this->route('customer')?->id;

        return [
            'type' => ['sometimes', 'string', Rule::in(['individual', 'company', 'government', 'vip'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255', 'required_if:type,company', 'required_if:type,government'],
            'phone' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('customers')
                    ->ignore($customerId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('center_id', $centerId)),
            ],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email:rfc,dns', 'max:255'],
            'notes' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
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
            'contact_name.required_if' => 'The contact name field is required when type is company.',
            'phone.unique' => 'This phone number already exists for another customer.',
        ];
    }
}
