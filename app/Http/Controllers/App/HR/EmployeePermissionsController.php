<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class EmployeePermissionsController extends Controller
{
    public function index(Employee $employee)
    {
        $this->authorize('view', $employee);

        $employee->load('user.roles', 'user.permissions');

        if (! $employee->user) {
            return response()->json([
                'has_user' => false,
                'roles' => [],
                'permissions' => [],
                'available_roles' => [],
                'available_permissions' => [],
            ]);
        }

        // Get all assignable roles for current tenant WITH their permissions
        $roles = Role::where('tenant_id', TenancyContext::tenantId())
            ->where('guard_name', 'web')
            ->where('name', '!=', 'super_admin')
            ->with('permissions:id,name')
            ->get(['id', 'name', 'label_ar', 'label_en']);

        // Get all permissions grouped by module
        $groupedPermissions = Permissions::byModule();

        return response()->json([
            'has_user' => true,
            'current_roles' => $employee->user->roles->pluck('id'), // Return IDs
            'current_permissions' => $employee->user->permissions->pluck('name'),
            'available_roles' => $roles,
            'grouped_permissions' => $groupedPermissions,
            'permission_descriptions' => collect(Permissions::all())->mapWithKeys(fn ($p) => [$p => Permissions::describe($p)]),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        if (! $employee->user) {
            return back()->with('error', __('messages.user_not_found'));
        }

        $this->authorize('update', $employee->user);

        $tenantId = TenancyContext::tenantId();
        $tenantPermissions = collect(Permissions::byModule())->flatten()->all();

        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => [
                'integer',
                Rule::exists('roles', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('guard_name', 'web'),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($tenantPermissions)],
        ]);

        $roles = Role::where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->whereIn('id', $validated['roles'] ?? [])
            ->get();

        foreach ($roles as $role) {
            $this->authorize('assignRole', [User::class, $role]);
        }

        foreach ($validated['permissions'] ?? [] as $permissionName) {
            abort_unless($request->user()->can($permissionName), 403);
        }

        DB::transaction(function () use ($employee, $roles, $validated, $tenantId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

            $employee->user->syncRoles($roles);
            $employee->user->syncPermissions($validated['permissions'] ?? []);
            $employee->user->logActivity('employee.permissions.updated', [
                'employee_id' => $employee->id,
            ]);
        });

        return back()->with('success', __('messages.updated_successfully'));
    }
}
