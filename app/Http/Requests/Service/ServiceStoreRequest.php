<?php

namespace App\Http\Requests\Service;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class ServiceStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('services.create');
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'department_id' => [
                'nullable',
                'integer',
                $this->centerExistsRule('departments'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
