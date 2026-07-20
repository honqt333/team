<?php

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('hr.employees.create');
    }

    public function rules(): array
    {
        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_employees', 'employee_number')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'national_id' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('hr_employees', 'national_id')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'hire_date' => ['required', 'date'],
            'job_title_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('hr_job_titles'),
            ],
            'department_id' => [
                'required',
                'integer',
                $this->centerExistsRule('departments'),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'gender' => ['nullable', 'in:male,female'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
        ];
    }
}
