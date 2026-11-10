<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Users;

use App\Models\Role;
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
            'centers.*' => [Rule::exists('centers', 'id')->where('tenant_id', TenancyContext::tenantId())],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('tenant_id', TenancyContext::tenantId())->where('guard_name', 'web'),
                function ($attribute, $value, $fail) {
                    $role = Role::find($value);

                    if (! $role || ! $this->user()->can('assignRole', [User::class, $role])) {
                        $fail(__('messages.cannot_edit_system_role'));
                    }
                }],
            'employee_id' => ['nullable', Rule::exists('hr_employees', 'id')->where('tenant_id', TenancyContext::tenantId())],
            'is_active' => ['sometimes', 'boolean'],
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
