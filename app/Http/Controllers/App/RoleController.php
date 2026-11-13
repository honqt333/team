<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(Permissions::USERS_VIEW); // Or create a specific ROLES_VIEW permission

        $roles = Role::where('tenant_id', TenancyContext::tenantId())
            ->where('guard_name', 'web')
            ->withCount(['users', 'permissions'])
            ->with('permissions')
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('label_ar', 'like', "%{$search}%")
                        ->orWhere('label_en', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->get();

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => (string) $request->query('search', ''),
            ],
            'groupedPermissions' => Permissions::byModule(),
            'permissionDescriptions' => collect(Permissions::all())->mapWithKeys(fn ($p) => [$p => Permissions::describe($p)]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize(Permissions::USERS_CREATE);

        return DB::transaction(function () use ($request) {

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::notIn(['super_admin']),
                    Rule::unique('roles')->where(fn ($query) => $query->where('tenant_id', TenancyContext::tenantId())),
                ],
                'label_ar' => ['nullable', 'string', 'max:255'],
                'label_en' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'permissions' => ['array'],
                'permissions.*' => ['string', Rule::in(Permissions::all()), function ($attribute, $value, $fail) use ($request) {
                    if (! $request->user()->can($value)) {
                        $fail(__('messages.cannot_edit_system_role'));
                    }
                }],
            ]);

            $role = Role::create([
                'name' => $validated['name'],
                'label_ar' => $validated['label_ar'] ?? null,
                'label_en' => $validated['label_en'] ?? null,
                'description' => $validated['description'] ?? null,
                'tenant_id' => TenancyContext::tenantId(),
                'guard_name' => 'web',
            ]);

            if (! empty($validated['permissions'])) {
                // Temporarily unset team ID to find global permissions
                $registrar = app(PermissionRegistrar::class);
                $currentTeamId = $registrar->getPermissionsTeamId();
                $registrar->setPermissionsTeamId(null);

                try {
                    $role->syncPermissions($validated['permissions']);
                } finally {
                    // Restore team ID
                    $registrar->setPermissionsTeamId($currentTeamId);
                }
            }

            $request->user()->logActivity('role.saved', ['role_id' => $role->id]);

            return back()->with('success', __('messages.saved_success'));
        });
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize(Permissions::USERS_UPDATE);

        return DB::transaction(function () use ($request, $role) {

            // Ensure role belongs to tenant
            if ($role->tenant_id !== TenancyContext::tenantId() || $role->guard_name !== 'web') {
                abort(403);
            }

            abort_unless($role->permissions->every(fn ($permission) => $request->user()->can($permission->name)), 403);

            // Protect Super Admin
            // 1. Prevent editing the 'super_admin' role itself
            if ($role->name === 'super_admin') {
                return back()->with('error', __('messages.cannot_edit_system_role'));
            }

            // 2. Prevent renaming ANY role TO 'super_admin'
            if ($request->input('name') === 'super_admin') {
                return back()->with('error', __('messages.cannot_edit_system_role'));
            }

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::notIn(['super_admin']),
                    Rule::unique('roles')->ignore($role->id)->where(fn ($query) => $query->where('tenant_id', TenancyContext::tenantId())),
                ],
                'label_ar' => ['nullable', 'string', 'max:255'],
                'label_en' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'permissions' => ['array'],
                'permissions.*' => ['string', Rule::in(Permissions::all()), function ($attribute, $value, $fail) use ($request) {
                    if (! $request->user()->can($value)) {
                        $fail(__('messages.cannot_edit_system_role'));
                    }
                }],
            ]);

            $role->update([
                'name' => $validated['name'],
                'label_ar' => $validated['label_ar'] ?? null,
                'label_en' => $validated['label_en'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            if (isset($validated['permissions'])) {
                // Temporarily unset team ID to find global permissions
                $registrar = app(PermissionRegistrar::class);
                $currentTeamId = $registrar->getPermissionsTeamId();
                $registrar->setPermissionsTeamId(null);

                try {
                    $role->syncPermissions($validated['permissions']);
                } finally {
                    // Restore team ID
                    $registrar->setPermissionsTeamId($currentTeamId);
                }
            }

            $request->user()->logActivity('role.saved', ['role_id' => $role->id]);

            return back()->with('success', __('messages.saved_success'));
        });
    }

    public function destroy(Role $role)
    {
        $this->authorize(Permissions::USERS_DELETE);

        if ($role->tenant_id !== TenancyContext::tenantId() || $role->guard_name !== 'web') {
            abort(403);
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', __('messages.cannot_delete_has_users'));
        }

        // Protect Super Admin
        if ($role->name === 'super_admin') {
            return back()->with('error', __('messages.cannot_delete_system_role'));
        }

        $role->delete();
        request()->user()->logActivity('role.deleted', ['role_id' => $role->id]);

        return back()->with('success', __('messages.deleted_success'));
    }
}
