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
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.qty' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
