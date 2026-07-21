<?php

declare(strict_types=1);

namespace App\Http\Requests\Quote;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class QuoteUpdateRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('quotes.update');
    }

    public function rules(): array
    {
        return [
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', 'in:draft,sent,approved,rejected,expired'],
        ];
    }
}
