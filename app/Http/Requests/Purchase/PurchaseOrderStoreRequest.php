<?php

declare(strict_types=1);

namespace App\Http\Requests\Purchase;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('purchasing.pos.create');
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('suppliers'),
            ],
            'po_date' => ['required', 'date', 'before_or_equal:today'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:po_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
