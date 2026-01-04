<?php

namespace App\Http\Requests\App\Users;

use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Support\TenancyContext;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('tenant_id', TenancyContext::tenantId());
                }),
            ],
            'password' => ['required', 'confirmed', 'min:8'],
            'centers' => ['required', 'array', 'min:1'],
            'centers.*' => ['exists:centers,id'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'employee_id' => ['nullable', 'exists:hr_employees,id'],
        ];
    }
}
