<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Users;

use App\Models\Role;
use App\Models\User;
use App\Support\TenancyContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // $this->user is the route parameter, which is injected as an instance of User model if type-hinted or bound
        // However, standard authorize check via gate checks the authenticated user against the policy
        // We need to fetch the target user to check permissions properly via policy if we passed arguments,
        // but simple 'update' check on class or model is handled by controller usually.
        // Actually, FormRequest authorize runs BEFORE controller method.
        // So we can assume the controller will handle 'authorize' call or we do it here.
        // The project rules say "Controller must use $this->authorize", so we can return true here
        // AND handle specifics in controller, OR do it here.
        // But usually standard Laravel practice is:

        // Let's rely on Controller's $this->authorize for the strict check, and here just return true or duplicate check.
        // Given existing code in EmployeeController uses $this->authorize inside methods, I'll return true here.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userParam = $this->route('user');
        $userId = $userParam instanceof User ? $userParam->id : $userParam;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)->where(function ($query) {
                    return $query->where('tenant_id', TenancyContext::tenantId());
                }),
            ],
            'password' => ['nullable', 'confirmed', 'min:8'], // Optional on update
            'centers' => ['required', 'array', 'min:1'],
            'centers.*' => [Rule::exists('centers', 'id')->where('tenant_id', TenancyContext::tenantId())],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('tenant_id', TenancyContext::tenantId())->where('guard_name', 'web'),
                function ($attribute, $value, $fail) {
                    $role = Role::find($value);

                    if (! $role || ! $this->user()->can('assignRole', [User::class, $role])) {
                        $fail(__('messages.cannot_edit_system_role'));
                    }
                }],
            'is_active' => ['sometimes', 'boolean'],
            // `prohibited`, NOT `forbidden`: Laravel has no `forbidden`
            // rule, so the validator would raise
            // BadMethodCallException("Method ...::validateForbidden does
            // not exist") — a 500 — instead of rejecting the request.
            // `prohibited` fails whenever the field carries any
            // non-empty value, which is exactly the intended guard:
            // the platform-admin flag is not tenant-settable.
            'is_system_admin' => ['prohibited'],
            // Note: employee_id linking is removed - it's automatic via EmployeeObserver
        ];
    }
}
