<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrder;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class AddItemRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        $workOrder = $this->route('work_order') ?? $this->route('workOrder');

        return $workOrder
            && $workOrder->canBeEdited()
            && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'service_id' => [
                'nullable',
                'integer',
                $this->centerExistsRule('services'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'department_id' => [
                'nullable',
                'integer',
                $this->centerExistsRule('departments'),
            ],
            'is_warranty' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
