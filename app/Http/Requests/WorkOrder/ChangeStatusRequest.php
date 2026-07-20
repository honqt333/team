<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order') ?? $this->route('workOrder');
        return $workOrder && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(WorkOrder::STATUSES ?? ['open', 'in_progress', 'on_hold', 'ready_for_qc', 'completed', 'cancelled'])],
            'reason' => ['required_if:status,on_hold', 'nullable', 'string', 'max:500'],
        ];
    }
}
