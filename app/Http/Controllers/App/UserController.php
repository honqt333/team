<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Users\StoreUserRequest;
use App\Http\Requests\App\Users\UpdateUserRequest;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;



class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        
        $tenantId = TenancyContext::tenantId();

        $users = User::where('tenant_id', $tenantId)
            ->with(['centers:id,name', 'employee:id,user_id,name_ar,name_en', 'roles']) // Eager load roles
            // Exclude Super Admin from the list
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'super_admin');
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
            'centers' => Center::where('tenant_id', $tenantId)->get(['id', 'name']),
            'roles' => Role::where('tenant_id', $tenantId)->get(['id', 'name', 'label_ar', 'label_en']),
            // Note: employees list removed - linking is automatic via EmployeeObserver
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        
        $validated = $request->validated();
        $tenantId = TenancyContext::tenantId();

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'current_center_id' => $validated['centers'][0], // Default to first center
            'is_active' => $request->boolean('is_active', true),
        ]);

        $centers = collect($validated['centers'])->mapWithKeys(function ($centerId) use ($tenantId) {
            return [$centerId => ['tenant_id' => $tenantId]];
        })->all();

        $user->centers()->sync($centers);

        // Sync Role
        if (!empty($validated['role_id'])) {
            $user->syncRoles([$validated['role_id']]);
        }

        return back()->with('success', __('messages.created_successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        
        $validated = $request->validated();
        
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);
        
        $tenantId = TenancyContext::tenantId();
        $centers = collect($validated['centers'])->mapWithKeys(function ($centerId) use ($tenantId) {
            return [$centerId => ['tenant_id' => $tenantId]];
        })->all();

        $user->centers()->sync($centers);

        // Ensure current_center_id is valid after sync
        if ($user->current_center_id && !array_key_exists($user->current_center_id, $centers)) {
            $newId = !empty($centers) ? array_key_first($centers) : null;
            $user->update(['current_center_id' => $newId]);
        } elseif (!$user->current_center_id && !empty($centers)) {
            $user->update(['current_center_id' => array_key_first($centers)]);
        }

        // Sync Role
        if (!empty($validated['role_id'])) {
            $user->syncRoles([$validated['role_id']]);
        } else {
             // If you want to allow unassigning roles:
             // $user->syncRoles([]);
        }

        // Note: Employee linking is automatic via EmployeeObserver
        // Manual linking from User side is not allowed

        return back()->with('success', __('messages.updated_successfully'));
    }


    /**
     * Toggle the active status of a user.
     */
    public function toggleActive(User $user)
    {
        $this->authorize('update', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', __('messages.cannot_deactivate_self'));
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        
        $user->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
