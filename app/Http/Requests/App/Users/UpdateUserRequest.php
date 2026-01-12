<?php

namespace App\Http\Requests\App\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Support\TenancyContext;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

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
            'centers.*' => ['exists:centers,id'],
            'role_id' => ['nullable', 'exists:roles,id'],
            // Note: employee_id linking is removed - it's automatic via EmployeeObserver
        ];
    }
}
