<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'customer_complaint' => ['nullable', 'string', 'max:5000'],
            'initial_assessment' => ['nullable', 'string', 'max:5000'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['exists:departments,id'],
            'lines' => ['nullable', 'array'],
            'lines.*.service_id' => ['nullable', 'exists:services,id'],
            'lines.*.description' => ['required_with:lines', 'string', 'max:255'],
            'lines.*.qty' => ['required_with:lines', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one service line is required.',
            'lines.min' => 'At least one service line is required.',
            'lines.*.description.required' => 'Each line must have a description.',
            'lines.*.qty.required' => 'Each line must have a quantity.',
            'lines.*.unit_price.required' => 'Each line must have a price.',
        ];
    }
}
