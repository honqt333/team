<?php

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('leaves.create') || $this->user()->can('hr.leaves.create');
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('hr_employees'),
            ],
            'leave_type' => ['required', Rule::in(['annual', 'sick', 'unpaid', 'maternity', 'pilgrimage', 'emergency'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
