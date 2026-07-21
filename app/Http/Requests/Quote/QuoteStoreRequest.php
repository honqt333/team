<?php

declare(strict_types=1);

namespace App\Http\Requests\Quote;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class QuoteStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('quotes.create');
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('customers'),
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('vehicles'),
            ],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
