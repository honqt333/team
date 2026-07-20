<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('parts.create') || $this->user()->can('inventory.parts.create');
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts', 'sku')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('inventory_categories'),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('inventory_units'),
            ],
            'default_sale_price' => ['required', 'numeric', 'min:0'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'barcode' => ['nullable', 'string', 'max:50'],
        ];
    }
}
