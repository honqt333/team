<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize(Permissions::USERS_VIEW); // Or create a specific ROLES_VIEW permission

        $roles = Role::where('tenant_id', TenancyContext::tenantId())
            ->withCount('users')
            ->with('permissions')
            ->get();

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'groupedPermissions' => Permissions::byModule(),
            'permissionDescriptions' => collect(Permissions::all())->mapWithKeys(fn($p) => [$p => Permissions::describe($p)]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize(Permissions::USERS_CREATE);

        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('roles')->where(fn ($query) => $query->where('tenant_id', TenancyContext::tenantId()))
            ],
            'label_ar' => ['nullable', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'label_ar' => $validated['label_ar'] ?? null,
            'label_en' => $validated['label_en'] ?? null,
            'description' => $validated['description'] ?? null,
            'tenant_id' => TenancyContext::tenantId(),
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', __('messages.saved_success'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize(Permissions::USERS_UPDATE);

        // Ensure role belongs to tenant
        if ($role->tenant_id !== TenancyContext::tenantId()) {
            abort(403);
        }

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
                Rule::unique('roles')->ignore($role->id)->where(fn ($query) => $query->where('tenant_id', TenancyContext::tenantId()))
            ],
            'label_ar' => ['nullable', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'label_ar' => $validated['label_ar'] ?? null,
            'label_en' => $validated['label_en'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', __('messages.saved_success'));
    }

    public function destroy(Role $role)
    {
        $this->authorize(Permissions::USERS_DELETE);

        if ($role->tenant_id !== TenancyContext::tenantId()) {
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

        return back()->with('success', __('messages.deleted_success'));
    }
}
