<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('inventory.adjust') || $this->user()->can('inventory.moves.create');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'integer',
                $this->centerExistsRule('warehouses'),
            ],
            'part_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('parts'),
            ],
            'qty' => ['required', 'numeric', 'not_in:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
            'allow_negative' => ['nullable', 'boolean'],
        ];
    }
}
