<?php

declare(strict_types=1);

namespace App\Http\Requests\Purchase;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('purchasing.returns.create');
    }

    public function rules(): array
    {
        return [
            'purchase_invoice_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('purchase_invoices'),
            ],
            'reason' => ['required', 'string', 'max:1000'],
            'return_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
