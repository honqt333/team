<?php

declare(strict_types=1);

namespace App\Http\Requests\Vehicle;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('crm.vehicles.create') || $this->user()->can('vehicles.create');
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('customers'),
            ],
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate_number')
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at'),
            ],
            'vin' => ['nullable', 'string', 'max:50'],
            'make_id' => ['nullable', 'integer', 'exists:vehicle_makes,id'],
            'model_id' => ['nullable', 'integer', 'exists:vehicle_models,id'],
            'color_id' => ['nullable', 'integer', 'exists:vehicle_colors,id'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
