<?php

declare(strict_types=1);

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeUpdateRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('hr.employees.update');
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id ?? $this->route('employee');

        return [
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'job_title_id' => [
                'sometimes',
                'integer',
                $this->tenantExistsRule('hr_job_titles'),
            ],
            'department_id' => [
                'sometimes',
                'integer',
                $this->centerExistsRule('departments'),
            ],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
