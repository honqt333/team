<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('purchasing.suppliers.view') 
            && $user->tenant_id === $supplier->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('purchasing.suppliers.update') 
            && $user->tenant_id === $supplier->tenant_id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('purchasing.suppliers.deactivate') 
            && $user->tenant_id === $supplier->tenant_id;
    }
}
