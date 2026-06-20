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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->phone;
        $whatsapp = $this->whatsapp;

        if ($phone) {
            $this->merge([
                'phone' => $this->normalizePhoneNumber($phone),
            ]);
        }

        if ($whatsapp) {
            $this->merge([
                'whatsapp' => $this->normalizePhoneNumber($whatsapp),
            ]);
        }
    }

    /**
     * Normalize a phone number to standard format (+9665xxxxxxxx).
     */
    private function normalizePhoneNumber(?string $number): ?string
    {
        if (!$number) {
            return null;
        }

        // Convert Arabic/Persian digits to English
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $number = str_replace($arabic, $english, $number);
        $number = str_replace($persian, $english, $number);

        // Remove all non-numeric characters (except +)
        $number = preg_replace('/[^\d+]/', '', $number);

        // If it starts with 00966, convert to +966
        if (str_starts_with($number, '00966')) {
            $number = '+966' . substr($number, 5);
        }

        // If it starts with 05, convert to +9665
        if (str_starts_with($number, '05') && strlen($number) === 10) {
            $number = '+966' . substr($number, 1);
        }

        // If it starts with 5, convert to +9665
        if (str_starts_with($number, '5') && strlen($number) === 9) {
            $number = '+966' . $number;
        }

        // If it starts with 966 and doesn't start with +, add +
        if (str_starts_with($number, '966') && !str_starts_with($number, '+')) {
            $number = '+' . $number;
        }

        return $number;
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
            'contact_name' => [
                Rule::requiredIf(fn () => in_array($this->type, ['company', 'government'])),
                'nullable',
                'string',
                'max:255'
            ],
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
