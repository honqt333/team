<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class CustomerMergeRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('crm.customers.update') || $this->user()->can('customers.merge');
    }

    public function rules(): array
    {
        return [
            'source_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('customers'),
            ],
            'target_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('customers'),
                'different:source_id',
            ],
        ];
    }
}
