<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use App\Models\User;
use App\Support\SystemPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdminUsersController extends Controller
{
    /**
     * Guard for every operation that creates, edits or removes a
     * system-admin account.
     *
     * The route group already applies the `super_admin` middleware, but
     * this check is kept in the controller as well: the role field on
     * an admin account selects what that account may do inside the
     * /system panel, and `super_admin` bypasses the permission list
     * entirely (AdminUser::hasPermission). A single mis-placed route
     * declaration would otherwise hand the whole platform away.
     *
     * @see EnsureSuperAdmin
     */
    private function ensureSuperAdmin(?Request $request = null): void
    {
        $user = $request?->user() ?? auth('admin')->user() ?? auth('web')->user();

        $isAllowed = ($user instanceof AdminUser && $user->isSuperAdmin())
            || ($user instanceof User && $user->is_system_admin);

        if (! $isAllowed) {
            abort(403, __('messages.system.admin_users.only_super_admin'));
        }
    }

    /**
     * List admin users.
     */
    public function index(Request $request): Response
    {
        $query = AdminUser::withCount('activityLogs');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $admins = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('System/AdminUsers/Index', [
            'admins' => $admins,
            'filters' => $request->only(['search', 'role']),
            'permissions' => AdminUser::getAvailablePermissions(),
        ]);
    }

    /**
     * Show create form.
     *
     * Creating an admin user is handled by a modal on the index page
     * (System/AdminUsers/Index.vue), which posts to the same store()
     * endpoint. This action used to render a standalone
     * "System/AdminUsers/Create" page, but that Vue file never
     * existed, so the route returned HTTP 500 with "Unable to locate
     * file in Vite manifest". Nothing linked to it either.
     *
     * It now redirects to the page that actually hosts the form, so
     * the route stays usable (a bookmark or a direct visit lands
     * somewhere that works) instead of erroring.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('system.admin-users.index');
    }

    /**
     * Store new admin user.
     */
    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin_users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:super_admin,admin,support',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Keep only abilities the registry knows about. A client could
        // otherwise store a hand-crafted ability name, which would then
        // be handed back to the front end and shown as a granted
        // permission it does not correspond to.
        $validated['permissions'] = SystemPermissions::filterValid(
            $validated['permissions'] ?? []
        );

        $admin = AdminUser::create($validated);

        // Log activity
        $actor = auth('admin')->user()
            ?? ($request->user() instanceof User ? AdminUser::where('email', $request->user()->email)->first() : null);
        $actor?->logActivity('create', AdminUser::class, $admin->id);

        return redirect()->route('system.admin-users.index')
            ->with('success', __('messages.system.admin_users.created_success'));
    }

    /**
     * Show edit form.
     *
     * Editing an admin user is handled by a modal on the index page
     * (System/AdminUsers/Index.vue), which puts to the same update()
     * endpoint. This action used to render a standalone
     * "System/AdminUsers/Edit" page that never existed as a Vue
     * file, so the route returned HTTP 500.
     *
     * It now redirects to the index page that hosts the form.
     */
    public function edit(AdminUser $adminUser): RedirectResponse
    {
        return redirect()->route('system.admin-users.index');
    }

    /**
     * Update admin user.
     */
    public function update(Request $request, AdminUser $adminUser)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin_users,email,'.$adminUser->id,
            'phone' => 'nullable|string|max:20',
            'password' => ['nullable', Password::defaults()],
            'role' => 'required|in:super_admin,admin,support',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Keep only abilities the registry knows about — see store().
        $validated['permissions'] = SystemPermissions::filterValid(
            $validated['permissions'] ?? []
        );

        $adminUser->update($validated);

        $actor = auth('admin')->user()
            ?? ($request->user() instanceof User ? AdminUser::where('email', $request->user()->email)->first() : null);
        $actor?->logActivity('update', AdminUser::class, $adminUser->id);

        return back()->with('success', __('messages.system.admin_users.updated_success'));
    }

    /**
     * Delete admin user.
     */
    public function destroy(Request $request, AdminUser $adminUser)
    {
        $this->ensureSuperAdmin($request);

        $actingUser = $request->user();

        // Prevent self-deletion
        if ($actingUser instanceof AdminUser && $actingUser->id === $adminUser->id) {
            return back()->withErrors(['error' => __('messages.system.admin_users.cannot_delete_self')]);
        }

        if ($actingUser instanceof User && $adminUser->email === $actingUser->email) {
            return back()->withErrors(['error' => __('messages.system.admin_users.cannot_delete_self')]);
        }

        $actor = auth('admin')->user()
            ?? ($actingUser instanceof User ? AdminUser::where('email', $actingUser->email)->first() : null);
        $actor?->logActivity('delete', AdminUser::class, $adminUser->id);

        $adminUser->delete();

        return redirect()->route('system.admin-users.index')
            ->with('success', __('messages.system.admin_users.deleted_success'));
    }

    /**
     * Activity logs.
     */
    public function activityLog(Request $request): Response
    {
        $query = AdminActivityLog::with('admin');

        if ($request->admin_id) {
            $query->where('admin_user_id', $request->admin_id);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();
        $admins = AdminUser::select('id', 'name')->get();

        return Inertia::render('System/AdminUsers/ActivityLog', [
            'logs' => $logs,
            'admins' => $admins,
            'filters' => $request->only(['admin_id', 'action']),
        ]);
    }
}
