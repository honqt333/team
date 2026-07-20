<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseInvoiceStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('purchasing.invoices.create');
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('suppliers'),
            ],
            'invoice_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
