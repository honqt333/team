<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TaxPeriod;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxPeriodPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVOICES_VIEW) || $user->can('crm.invoices.view');
    }

    public function view(User $user, TaxPeriod $taxPeriod): bool
    {
        return ($user->can(Permissions::INVOICES_VIEW) || $user->can('crm.invoices.view'))
            && (int) $user->tenant_id === (int) $taxPeriod->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::INVOICES_CREATE);
    }

    public function update(User $user, TaxPeriod $taxPeriod): bool
    {
        return $user->can(Permissions::INVOICES_CREATE)
            && (int) $user->tenant_id === (int) $taxPeriod->tenant_id
            && $taxPeriod->status === 'draft';
    }

    public function post(User $user, TaxPeriod $taxPeriod): bool
    {
        return $user->can(Permissions::INVOICES_CREATE)
            && (int) $user->tenant_id === (int) $taxPeriod->tenant_id
            && $taxPeriod->status === 'draft';
    }

    public function delete(User $user, TaxPeriod $taxPeriod): bool
    {
        return $user->can(Permissions::INVOICES_CREATE)
            && (int) $user->tenant_id === (int) $taxPeriod->tenant_id
            && $taxPeriod->status === 'draft';
    }
}
