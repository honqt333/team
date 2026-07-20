<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('departments.create');
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
