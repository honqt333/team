<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Users;

use App\Models\User;
use App\Support\TenancyContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, ValidationRule|array<mixed>|string>
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
            // `prohibited`, NOT `forbidden`: Laravel has no `forbidden`
            // rule, so the validator would raise
            // BadMethodCallException("Method ...::validateForbidden does
            // not exist") — a 500 — instead of rejecting the request.
            // `prohibited` fails whenever the field carries any
            // non-empty value, which is exactly the intended guard:
            // the platform-admin flag is not tenant-settable.
            'is_system_admin' => ['prohibited'],
        ];
    }
}
