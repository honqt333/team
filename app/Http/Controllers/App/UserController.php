<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Users\StoreUserRequest;
use App\Http\Requests\App\Users\UpdateUserRequest;
use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($empQuery) use ($search) {
                            $empQuery->where('name_ar', 'like', "%{$search}%")
                                ->orWhere('name_en', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('role_id'), function ($query) use ($request) {
                $roleId = (int) $request->input('role_id');
                $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
            })
            ->when($request->filled('center_id'), function ($query) use ($request) {
                $centerId = (int) $request->input('center_id');
                $query->whereHas('centers', fn ($q) => $q->where('centers.id', $centerId));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');

                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'role_id' => $request->filled('role_id') ? (int) $request->input('role_id') : null,
                'center_id' => $request->filled('center_id') ? (int) $request->input('center_id') : null,
                'status' => (string) $request->input('status', ''),
            ],
            'centers' => Center::where('tenant_id', $tenantId)->get(['id', 'name']),
            'roles' => Role::where('tenant_id', $tenantId)
                ->where('guard_name', 'web')
                ->where('name', '!=', 'super_admin')
                ->get(['id', 'name', 'label_ar', 'label_en']),
            'userLimit' => $this->getUserPlanUsage($tenantId),
            // Note: employees list removed - linking is automatic via EmployeeObserver
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $tenantId = TenancyContext::tenantId();

        if ($request->boolean('is_active', true)) {
            if ($limitError = $this->checkUserPlanLimit($tenantId)) {
                return back()->with('error', $limitError);
            }
        }

        return DB::transaction(function () use ($request, $tenantId) {

            $validated = $request->validated();

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
            if (! empty($validated['role_id'])) {
                $user->syncRoles([$validated['role_id']]);
            }

            $user->logActivity('user.created');

            return back()->with('success', __('messages.created_successfully'));
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $tenantId = TenancyContext::tenantId();

        if ($request->has('is_active') && $request->boolean('is_active') && ! $user->is_active) {
            if ($limitError = $this->checkUserPlanLimit($tenantId)) {
                return back()->with('error', $limitError);
            }
        }

        return DB::transaction(function () use ($request, $user) {

            $validated = $request->validated();

            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
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
            if ($user->current_center_id && ! array_key_exists($user->current_center_id, $centers)) {
                $newId = ! empty($centers) ? array_key_first($centers) : null;
                $user->forceFill(['current_center_id' => $newId])->save();
            } elseif (! $user->current_center_id && ! empty($centers)) {
                $user->forceFill(['current_center_id' => array_key_first($centers)])->save();
            }

            // Sync Role
            if (array_key_exists('role_id', $validated)) {
                if (! empty($validated['role_id'])) {
                    $user->syncRoles([$validated['role_id']]);
                } else {
                    $user->syncRoles([]);
                }
            }

            // Note: Employee linking is automatic via EmployeeObserver
            // Manual linking from User side is not allowed

            $user->logActivity('user.updated');

            return back()->with('success', __('messages.updated_successfully'));
        });
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

        if (! $user->is_active) {
            if ($limitError = $this->checkUserPlanLimit(TenancyContext::tenantId())) {
                return back()->with('error', $limitError);
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $user->logActivity('user.updated');

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Get the tenant's active user count and subscription max_users limit.
     *
     * @return array{active_count: int, max_users: int|null}
     */
    private function getUserPlanUsage(?int $tenantId): array
    {
        if (! $tenantId) {
            return ['active_count' => 0, 'max_users' => null];
        }

        $activeUsersCount = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return ['active_count' => $activeUsersCount, 'max_users' => null];
        }

        $activeSubscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trial', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('ends_at')
            ->with('plan')
            ->first();

        $limits = $activeSubscription?->plan?->limits;

        if (! is_array($limits)) {
            return ['active_count' => $activeUsersCount, 'max_users' => null];
        }

        $rawMax = $limits['max_users'] ?? $limits['users'] ?? null;

        if ($rawMax === null) {
            return ['active_count' => $activeUsersCount, 'max_users' => null];
        }

        $maxUsers = (int) $rawMax;

        return [
            'active_count' => $activeUsersCount,
            'max_users' => ($maxUsers > 0 && $maxUsers < 999) ? $maxUsers : null,
        ];
    }

    /**
     * Check whether the tenant's active subscription allows adding/activating another user.
     */
    private function checkUserPlanLimit(?int $tenantId): ?string
    {
        $usage = $this->getUserPlanUsage($tenantId);

        if ($usage['max_users'] !== null && $usage['active_count'] >= $usage['max_users']) {
            return __('company_profile.users.max_limit_reached', ['max' => $usage['max_users']]);
        }

        return null;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();
        $user->logActivity('user.deleted');

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
