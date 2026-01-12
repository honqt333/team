<?php

namespace App\Observers;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\WelcomeEmployeeInvitation;
use Spatie\Permission\PermissionRegistrar;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     * Creates a user account automatically for every new employee.
     */
    public function created(Employee $employee): void
    {
        $this->handleUserCreation($employee);
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        // If email changed and no user linked, try to create/link user
        if ($employee->isDirty('email') && !$employee->user_id) {
            $this->handleUserCreation($employee);
        }

        // Handle deactivation logic
        if ($employee->isDirty('status') && in_array($employee->status, ['terminated', 'resigned'])) {
            if ($employee->user) {
                $employee->user->update(['is_active' => false]);
            }
        }

        // Handle reactivation
        if ($employee->isDirty('status') && $employee->status === 'active') {
            if ($employee->user && !$employee->user->is_active) {
                $employee->user->update(['is_active' => true]);
            }
        }
    }

    /**
     * Handle automatic user creation for employee.
     * 
     * New Logic:
     * 1. Every employee gets a User account automatically
     * 2. Every employee gets the 'employee' role (self-service portal access)
     * 3. If job title has a default_role_name, that role is ALSO assigned
     * 4. Invitation email is sent automatically
     */
    protected function handleUserCreation(Employee $employee): void
    {
        // Skip if no email
        if (empty($employee->email)) {
            \Log::warning("EmployeeObserver: Skipping user creation for employee {$employee->id} - no email");
            return;
        }

        // Skip if already linked
        if ($employee->user_id) {
            return;
        }

        // Check if user with this email already exists
        $user = User::where('email', $employee->email)->first();
        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = User::create([
                'name' => $employee->name_en ?? $employee->name_ar ?? 'Employee',
                'email' => $employee->email,
                'password' => Hash::make(Str::random(32)), // Random password - will be set via invite
                'is_active' => true, // Active by default now
                'email_verified_at' => now(), // Auto-verified since we're sending invite
                'tenant_id' => $employee->tenant_id,
                'current_tenant_id' => $employee->tenant_id,
                'current_center_id' => $employee->center_id,
            ]);

            // Attach to center
            if ($employee->center_id) {
                $user->centers()->attach($employee->center_id, ['tenant_id' => $employee->tenant_id]);
            }
        } else {
            // Existing user found - ensure they're attached to employee's center
            if ($employee->center_id && !$user->centers->contains($employee->center_id)) {
                $user->centers()->attach($employee->center_id, ['tenant_id' => $employee->tenant_id]);
            }
            // Update current center if not set
            if (!$user->current_center_id && $employee->center_id) {
                $user->update(['current_center_id' => $employee->center_id]);
            }
        }

        // Link User to Employee
        $employee->user_id = $user->id;
        $employee->saveQuietly();

        // Set team context for role assignment
        app(PermissionRegistrar::class)->setPermissionsTeamId($employee->tenant_id);

        // Always assign base 'employee' role
        if (!$user->hasRole('employee')) {
            $user->assignRole('employee');
        }

        // Assign additional role from Job Title if defined
        if ($employee->jobTitle && $employee->jobTitle->default_role_name) {
            $additionalRole = $employee->jobTitle->default_role_name;
            if ($additionalRole !== 'employee' && !$user->hasRole($additionalRole)) {
                $user->assignRole($additionalRole);
            }
        }

        // Send invitation email for new users
        if ($isNewUser) {
            $user->notify(new WelcomeEmployeeInvitation());
            \Log::info("EmployeeObserver: Created user and sent invite for employee {$employee->id}");
        }
    }
}

