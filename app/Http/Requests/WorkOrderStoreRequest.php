<?php

namespace App\Http\Requests;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $tenantId = $user->tenant_id;
        $centerId = $user->current_center_id;

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId)
                    ->whereNull('deleted_at'),
            ],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId),
                // Vehicle must belong to selected customer
                function ($attribute, $value, $fail) {
                    if ($value && $this->customer_id) {
                        $vehicle = \App\Models\Vehicle::find($value);
                        if ($vehicle && $vehicle->customer_id != $this->customer_id) {
                            $fail(__('validation.vehicle_customer_mismatch'));
                        }
                    }
                },
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(WorkOrder::STATUSES),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            
            // New fields
            'customer_complaint' => ['nullable', 'string', 'max:5000'],
            'initial_assessment' => ['nullable', 'string', 'max:5000'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'entry_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:entry_date'],
            
            // Departments
            'departments' => ['nullable', 'array'],
            'departments.*' => [
                'integer',
                Rule::exists('departments', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId),
            ],
            
            // Damage marks
            'damage_marks' => ['nullable', 'array'],
            'damage_marks.*.x' => ['required', 'numeric'],
            'damage_marks.*.y' => ['required', 'numeric'],
            'damage_marks.*.color' => ['nullable', 'string', Rule::in(['red', 'blue', 'gray'])],
            'damage_marks.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => __('validation.customer_required'),
            'vehicle_id.required' => __('validation.vehicle_required'),
            'expected_end_date.after_or_equal' => __('validation.expected_end_date_after_entry'),
        ];
    }
}
