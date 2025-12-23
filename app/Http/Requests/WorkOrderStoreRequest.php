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
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => __('validation.work_order_items_required'),
            'items.min' => __('validation.work_order_items_min'),
            'items.*.title.required' => __('validation.item_title_required'),
            'items.*.qty.required' => __('validation.item_qty_required'),
            'items.*.qty.min' => __('validation.item_qty_min'),
            'items.*.unit_price.required' => __('validation.item_price_required'),
        ];
    }
}
