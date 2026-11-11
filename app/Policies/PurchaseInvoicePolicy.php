<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\User;
use App\Support\Permissions;
use App\Support\TenancyContext;

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
            && $this->sharesDocumentScope($user, $invoice);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_CREATE);
    }

    public function update(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::PURCHASE_INVOICES_UPDATE)
            && $this->sharesDocumentScope($user, $invoice);
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
            && $this->sharesDocumentScope($user, $return);
    }

    public function createReturn(User $user, PurchaseInvoice $invoice): bool
    {
        return $user->can(Permissions::RETURNS_CREATE)
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function updateReturn(User $user, PurchaseReturnInvoice $return): bool
    {
        return $user->can(Permissions::RETURNS_UPDATE)
            && $this->sharesDocumentScope($user, $return);
    }

    public function deleteReturn(User $user, PurchaseReturnInvoice $return): bool
    {
        return $user->can(Permissions::RETURNS_DELETE)
            && $user->tenant_id === $return->tenant_id;
    }

    private function sharesDocumentScope(User $user, PurchaseInvoice|PurchaseReturnInvoice $document): bool
    {
        $center = TenancyContext::centerId() ?? $user->current_center_id;

        return $user->tenant_id === $document->tenant_id
            && (string) $center === (string) $document->center_id;
    }
}
