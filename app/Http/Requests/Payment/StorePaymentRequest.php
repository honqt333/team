<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Enums\PaymentType;
use App\Http\Requests\Concerns\TenantAware;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('payments.create');
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(Payment::METHODS ?? ['cash', 'mada', 'visa', 'mastercard', 'transfer', 'apple_pay', 'stc_pay', 'tabby'])],
            'type' => ['required', Rule::in(array_column(PaymentType::cases(), 'value'))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'work_order_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('work_orders'),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('invoices'),
            ],
        ];
    }
}
