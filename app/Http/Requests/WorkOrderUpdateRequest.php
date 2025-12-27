<?php

namespace App\Http\Requests;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderUpdateRequest extends FormRequest
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
                'sometimes',
                'integer',
                Rule::exists('customers', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId)
                    ->whereNull('deleted_at'),
            ],
            'vehicle_id' => [
                'sometimes',
                'integer',
                Rule::exists('vehicles', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId),
                function ($attribute, $value, $fail) {
                    $customerId = $this->customer_id ?? $this->route('work_order')->customer_id;
                    if ($value && $customerId) {
                        $vehicle = \App\Models\Vehicle::find($value);
                        if ($vehicle && $vehicle->customer_id != $customerId) {
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
            'damage_marks.*.color' => ['nullable', 'string'],
            'damage_marks.*.description' => ['nullable', 'string', 'max:500'],

            // Photos
            'photos' => ['nullable', 'array', 'max:20'],
            'photos.*.id' => ['nullable', 'integer', Rule::exists('work_order_photos', 'id')],
            'photos.*.file' => ['nullable', 'file', 'image', 'max:5120'], // Max 5MB
            'photos.*.type' => ['nullable', 'string', Rule::in(['general', 'before', 'after', 'damage'])],
            'photos.*.caption' => ['nullable', 'string', 'max:255'],

            // Items (if needed)
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.qty' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
