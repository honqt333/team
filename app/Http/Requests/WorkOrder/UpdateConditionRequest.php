<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order') ?? $this->route('workOrder');
        return $workOrder && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'fuel_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'damage_marks' => ['nullable', 'array'],
            'damage_marks.*.x' => ['required_with:damage_marks', 'numeric', 'between:0,100'],
            'damage_marks.*.y' => ['required_with:damage_marks', 'numeric', 'between:0,100'],
            'damage_marks.*.color' => ['required_with:damage_marks', 'string', 'in:red,blue,gray'],
            'damage_marks.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
