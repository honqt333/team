<?php

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceClockRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('hr.attendance.manage') || $this->user()->can('employee.attendance.view');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:clock_in,clock_out'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
