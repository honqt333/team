<?php

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\User;
use App\Support\Permissions;

class PurchaseInvoicePolicy
{
    /* ------------------------------------------------------------------
     | Purchase Invoices
     |-------------------------------------------------------------------*/

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_VIEW);
    }

    public function view(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_VIEW)
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_CREATE);
    }

    public function update(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_UPDATE)
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function delete(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_DELETE)
            && $user->tenant_id === $invoice->tenant_id;
    }

    /* ------------------------------------------------------------------
     | Payments (refunds + payments share the same gate)
     |-------------------------------------------------------------------*/

    public function managePayments(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::PURCHASE_PAYMENTS_MANAGE)
            && $user->tenant_id === $invoice->tenant_id;
    }

    /* ------------------------------------------------------------------
     | Purchase Returns
     |-------------------------------------------------------------------*/

    public function viewReturn(User $user, PurchaseReturnInvoice $return): bool
    {
        return $user->can(Permissions::RETURNS_VIEW)
            && $user->tenant_id === $return->tenant_id;
    }

    public function createReturn(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::RETURNS_CREATE)
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function updateReturn(User $user, PurchaseReturnInvoice $return): bool
    {
        return $user->can(Permissions::RETURNS_UPDATE)
            && $user->tenant_id === $return->tenant_id;
    }

    public function deleteReturn(User $user, PurchaseReturnInvoice $return): bool
    {
        return $user->can(Permissions::RETURNS_DELETE)
            && $user->tenant_id === $return->tenant_id;
    }
}

