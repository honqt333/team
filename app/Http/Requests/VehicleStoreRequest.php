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
                function ($attribute, $value, $fail) {
                    // Accept __other__ as valid
                    if ($value === '__other__') {
                        return;
                    }
                    // If numeric, must exist in database
                    if (!empty($value) && is_numeric($value)) {
                        if (!\App\Models\VehicleMake::find($value)) {
                            $fail(__('validation.exists', ['attribute' => __('vehicles.form.make')]));
                        }
                    }
                },
            ],
            'model_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Accept __other__ as valid
                    if ($value === '__other__') {
                        return;
                    }
                    // If numeric, must exist in database and belong to selected make
                    if (!empty($value) && is_numeric($value)) {
                        $model = \App\Models\VehicleModel::find($value);
                        if (!$model) {
                            $fail(__('validation.exists', ['attribute' => __('vehicles.form.model')]));
                        } elseif ($this->make_id && is_numeric($this->make_id) && $model->make_id != $this->make_id) {
                            $fail(__('validation.model_make_mismatch'));
                        }
                    }
                },
            ],
            'make_other' => [
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    // Required if make_id is __other__ or empty
                    if (($this->make_id === '__other__' || empty($this->make_id)) && empty($value)) {
                        $fail(__('validation.required', ['attribute' => __('vehicles.form.make_other')]));
                    }
                },
            ],
            'model_other' => [
                'nullable',
                'string',
                'max:100',
            ],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'max:50'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
