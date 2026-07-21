<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVOICES_VIEW);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can(Permissions::INVOICES_VIEW)
            && $user->tenant_id === $invoice->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::INVOICES_CREATE);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // The legacy code referenced 'invoices.edit' which was
        // never defined as a permission. Invoicing in this app
        // is two-state (create + extra_discount); treat 'edit'
        // as 'create' since the underlying `status === 'draft'`
        // check enforces the only-editable-in-draft semantics.
        return $user->can(Permissions::INVOICES_CREATE)
            && $user->tenant_id === $invoice->tenant_id
            && $invoice->status === 'draft'; // Can only update drafts
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // The legacy code referenced 'invoices.delete' which
        // was never defined as a permission. There is no separate
        // delete permission; create-level access is the closest
        // available signal, and the draft-state check below
        // gates the actual destructive operation.
        return $user->can(Permissions::INVOICES_CREATE)
            && $user->tenant_id === $invoice->tenant_id
            && $invoice->status === 'draft'; // Can only delete drafts
    }

    /**
     * Determine whether the user can record payments.
     *
     * Invoicing payments are part of the purchasing/payment
     * flow; the only existing payment permission is
     * `purchasing.payments.manage`, so we use that as the gate.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->can(Permissions::PURCHASE_PAYMENTS_MANAGE)
            && $user->tenant_id === $invoice->tenant_id
            && $invoice->payment_status !== 'paid';
    }
}
