<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Enums\PaymentType;
use App\Http\Requests\Concerns\TenantAware;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('payments.update');
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['sometimes', Rule::in(Payment::METHODS ?? ['cash', 'mada', 'visa', 'mastercard', 'transfer', 'apple_pay', 'stc_pay', 'tabby'])],
            'type' => ['sometimes', Rule::in(array_column(PaymentType::cases(), 'value'))],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:9999999'],
            'payment_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
