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
            && $user->tenant_id === $contract->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_CREATE);
    }

    public function update(User $user, EmployeeContract $contract): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_UPDATE)
            && $user->tenant_id === $contract->tenant_id;
    }

    public function delete(User $user, EmployeeContract $contract): bool
    {
        return $user->can(Permissions::HR_CONTRACTS_DELETE)
            && $user->tenant_id === $contract->tenant_id;
    }
}
