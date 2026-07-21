<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Support\Permissions;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::SUPPLIERS_VIEW);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can(Permissions::SUPPLIERS_VIEW)
            && $user->tenant_id === $supplier->tenant_id
            && ($supplier->center_id === $user->current_center_id || is_null($supplier->center_id));
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::SUPPLIERS_CREATE);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can(Permissions::SUPPLIERS_UPDATE)
            && $user->tenant_id === $supplier->tenant_id
            && ($supplier->center_id === $user->current_center_id || is_null($supplier->center_id));
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        // Note: the legacy code referenced
        // 'purchasing.suppliers.deactivate' which is not in the
        // Permissions registry. SUPPLIERS_DESTROY is the closest
        // available constant.
        return $user->can(Permissions::SUPPLIERS_DESTROY)
            && $user->tenant_id === $supplier->tenant_id
            && ($supplier->center_id === $user->current_center_id || is_null($supplier->center_id));
    }
}
