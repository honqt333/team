<?php

declare(strict_types=1);

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
            'status' => ['nullable', Rule::in(WorkOrder::STATUSES ?? ['open', 'in_progress', 'on_hold', 'ready_for_qc', 'completed', 'cancelled'])],
            'reason' => ['required_without:status', 'nullable', 'string', 'max:500'],
        ];
    }
}
