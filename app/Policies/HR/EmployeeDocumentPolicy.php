<?php

declare(strict_types=1);

namespace App\Policies\HR;

use App\Models\HR\EmployeeDocument;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization for employee documents.
 *
 * Employee documents are sensitive: they hold contracts, national IDs,
 * medical certificates and visa copies. They are therefore governed by
 * the same permission set as the employee record itself rather than a
 * document-specific set — a user who may edit an employee may attach a
 * document to them.
 *
 * Every method also enforces tenant isolation, so a document can never
 * be read or deleted from another tenant even if the model scope were
 * bypassed.
 */
class EmployeeDocumentPolicy
{
    /**
     * Can the user attach a document to an employee?
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::HR_EMPLOYEES_UPDATE);
    }

    /**
     * Can the user view/download this document?
     */
    public function view(User $user, EmployeeDocument $document): bool
    {
        return $user->can(Permissions::HR_EMPLOYEES_VIEW)
            && $user->tenant_id === $document->tenant_id;
    }

    /**
     * Can the user delete this document?
     */
    public function delete(User $user, EmployeeDocument $document): bool
    {
        return $user->can(Permissions::HR_EMPLOYEES_DELETE)
            && $user->tenant_id === $document->tenant_id;
    }
}
