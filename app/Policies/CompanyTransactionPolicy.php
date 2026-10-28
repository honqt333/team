<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyTransaction;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization for the company financial transactions.
 *
 * A company transaction is a company-level financial entry: approving
 * one issues a real invoice (or purchase invoice) and records a
 * payment. Before this policy existed the controller had no
 * authorization at all, so ANY authenticated user in the tenant could
 * create, edit, delete and APPROVE financial entries — and approve()
 * is what creates the invoice and the payment.
 *
 * The permissions are the invoice ones, which is where these entries
 * end up:
 *
 *   - INVOICES_VIEW   read the transactions
 *   - INVOICES_CREATE write them, and approve them (approving issues
 *                     the invoice)
 *
 * The seeded roles that hold INVOICES_CREATE are the financial ones —
 * super_admin, branch_manager and accountant. The operational roles
 * (receptionist, technician, hr, employee) do not, and must not be
 * able to post company financial entries.
 */
class CompanyTransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVOICES_VIEW);
    }

    public function view(User $user, CompanyTransaction $transaction): bool
    {
        return $user->can(Permissions::INVOICES_VIEW)
            && $user->tenant_id === $transaction->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::INVOICES_CREATE);
    }

    public function update(User $user, CompanyTransaction $transaction): bool
    {
        if (! $user->can(Permissions::INVOICES_CREATE)) {
            return false;
        }

        if ($user->tenant_id !== $transaction->tenant_id) {
            return false;
        }

        // An approved transaction has already produced an invoice and
        // a payment; editing it afterwards would leave the two out of
        // step.
        return $transaction->status !== 'approved';
    }

    public function delete(User $user, CompanyTransaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }

    /**
     * Approving issues the invoice and records the payment — the
     * heaviest operation in this feature.
     */
    public function approve(User $user, CompanyTransaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
