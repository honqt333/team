<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeePermissionsController extends Controller
{
    public function index(Employee $employee)
    {
        $this->authorize('view', $employee);
        
        $employee->load('user.roles', 'user.permissions');

        if (!$employee->user) {
            return response()->json([
                'has_user' => false,
                'roles' => [],
                'permissions' => [],
                'available_roles' => [],
                'available_permissions' => [],
            ]);
        }

        // Get all roles for current tenant WITH their permissions
        $roles = Role::where('tenant_id', TenancyContext::tenantId())
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
            'permission_descriptions' => collect(Permissions::all())->mapWithKeys(fn($p) => [$p => Permissions::describe($p)]),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee); 

        if (!$employee->user) {
            return back()->with('error', __('messages.user_not_found'));
        }

        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'], // Validate IDs
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        // Sync Roles by ID
        $employee->user->syncRoles($validated['roles'] ?? []);

        // Sync Direct Permissions
        $employee->user->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
