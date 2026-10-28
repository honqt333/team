<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HR\EmployeeContract;
use App\Models\User;
use App\Support\Permissions;

class EmployeeContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_VIEW);
    }

    public function view(User $user, EmployeeContract $contract): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_VIEW)
            && $user->tenant_id === $contract->tenant_id
            && $this->employeeIsInScope($user, $contract);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_CREATE);
    }

    public function update(User $user, EmployeeContract $contract): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_UPDATE)
            && $user->tenant_id === $contract->tenant_id
            && $this->employeeIsInScope($user, $contract);
    }

    public function delete(User $user, EmployeeContract $contract): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_DELETE)
            && $user->tenant_id === $contract->tenant_id
            && $this->employeeIsInScope($user, $contract);
    }

    /**
     * Is the employee behind the contract inside the user's scope?
     *
     * A contract carries the salary details and the content of the
     * agreement, so it has to follow the centre of its employee. The
     * tenant super admin manages every branch; everybody else only
     * their own centre — the same rule the employee list uses.
     */
    private function employeeIsInScope(User $user, EmployeeContract $contract): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $employee = $contract->employee;

        if (! $employee) {
            return false;
        }

        if ($employee->center_id === null || $user->current_center_id === null) {
            return true;
        }

        return (int) $employee->center_id === (int) $user->current_center_id;
    }
}
