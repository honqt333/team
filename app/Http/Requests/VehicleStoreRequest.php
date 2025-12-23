<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleStoreRequest extends FormRequest
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
                    ->where('center_id', $centerId),
            ],
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles')
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId),
            ],
            'make_id' => [
                'nullable', 
                'integer', 
                'exists:vehicle_makes,id',
                // Require either make_id or make_other
                function ($attribute, $value, $fail) {
                    if (empty($value) && $value !== '__other__' && empty($this->make_other)) {
                        $fail('يجب اختيار الماركة');
                    }
                },
            ],
            'model_id' => [
                'nullable',
                'integer',
                'exists:vehicle_models,id',
                // Ensure model belongs to make
                function ($attribute, $value, $fail) {
                    if ($value && $this->make_id) {
                        $model = \App\Models\VehicleModel::find($value);
                        if ($model && $model->make_id != $this->make_id) {
                            $fail(__('validation.model_make_mismatch'));
                        }
                    }
                },
            ],
            'make_other' => [
                'nullable',
                'string',
                'max:100',
                'required_if:make_id,null,__other__',
            ],
            'model_other' => [
                'nullable',
                'string',
                'max:100',
                'required_if:model_id,null,__other__',
            ],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'max:50'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
