<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdminUsersController extends Controller
{
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
     */
    public function create(): Response
    {
        return Inertia::render('System/AdminUsers/Create', [
            'permissions' => AdminUser::getAvailablePermissions(),
        ]);
    }

    /**
     * Store new admin user.
     */
    public function store(Request $request)
    {
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

        $admin = AdminUser::create($validated);

        // Log activity
        auth('admin')->user()?->logActivity('create', AdminUser::class, $admin->id);

        return redirect()->route('system.admin-users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    /**
     * Show edit form.
     */
    public function edit(AdminUser $adminUser): Response
    {
        $recentActivity = $adminUser->activityLogs()
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('System/AdminUsers/Edit', [
            'admin' => $adminUser,
            'permissions' => AdminUser::getAvailablePermissions(),
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Update admin user.
     */
    public function update(Request $request, AdminUser $adminUser)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin_users,email,' . $adminUser->id,
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

        $adminUser->update($validated);

        auth('admin')->user()?->logActivity('update', AdminUser::class, $adminUser->id);

        return back()->with('success', 'تم تحديث المستخدم بنجاح');
    }

    /**
     * Delete admin user.
     */
    public function destroy(AdminUser $adminUser)
    {
        // Prevent self-deletion
        if (auth('admin')->id() === $adminUser->id) {
            return back()->withErrors(['error' => 'لا يمكنك حذف حسابك الحالي']);
        }

        auth('admin')->user()?->logActivity('delete', AdminUser::class, $adminUser->id);

        $adminUser->delete();

        return redirect()->route('system.admin-users.index')
            ->with('success', 'تم حذف المستخدم');
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
