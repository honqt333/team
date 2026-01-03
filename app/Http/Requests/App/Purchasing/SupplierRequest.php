<?php

namespace App\Http\Requests\App\Purchasing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $supplierId = $this->route('supplier') ? $this->route('supplier')->id : null;

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('suppliers')->where('tenant_id', $tenantId)->ignore($supplierId),
            ],
            'type' => 'required|in:parts,services',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'building_number' => 'nullable|string|max:20',
            'district' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'tax_number' => 'nullable|string|max:20',
            'cr_number' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
