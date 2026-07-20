<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class LeaveApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leaves.approve') || $this->user()->can('hr.leaves.approve');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
