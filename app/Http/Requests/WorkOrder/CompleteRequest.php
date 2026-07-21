<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order') ?? $this->route('workOrder');

        return $workOrder
            && $workOrder->allItemsCompleted()
            && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'exit_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_deferred' => ['nullable', 'boolean'],
            'due_date' => ['required_if:is_deferred,true', 'nullable', 'date', 'after_or_equal:exit_date'],
        ];
    }
}
