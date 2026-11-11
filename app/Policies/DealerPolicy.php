<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dealer;
use App\Models\User;
use App\Support\Permissions;

class DealerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::CUSTOMERS_VIEW);
    }

    public function view(User $user, Dealer $dealer): bool
    {
        return $user->can(Permissions::CUSTOMERS_VIEW)
            && (int) $user->tenant_id === (int) $dealer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::CUSTOMERS_CREATE);
    }

    public function update(User $user, Dealer $dealer): bool
    {
        return $user->can(Permissions::CUSTOMERS_UPDATE)
            && (int) $user->tenant_id === (int) $dealer->tenant_id;
    }

    public function delete(User $user, Dealer $dealer): bool
    {
        return $user->can(Permissions::CUSTOMERS_DELETE)
            && (int) $user->tenant_id === (int) $dealer->tenant_id;
    }
}
