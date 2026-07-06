<?php
namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Support\Permissions;
class QuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quotes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::QUOTES_VIEW);
    }

    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): bool
    {
        return $user->can(Permissions::QUOTES_VIEW);
    }

    /**
     * Determine whether the user can create quotes.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::QUOTES_CREATE);
    }

    /**
     * Determine whether the user can update the quote.
     */
    public function update(User $user, Quote $quote): bool
    {
        if (!$user->can(Permissions::QUOTES_UPDATE)) {
            return false;
        }

        // Can only update draft or sent quotes
        return $quote->canBeEdited();
    }

    /**
     * Determine whether the user can delete the quote.
     */
    public function delete(User $user, Quote $quote): bool
    {
        if (!$user->can(Permissions::QUOTES_DELETE)) {
            return false;
        }

        // Can only delete draft quotes
        return $quote->isDraft();
    }

    /**
     * Determine whether the user can approve the quote.
     */
    public function approve(User $user, Quote $quote): bool
    {
        if (!$user->can(Permissions::QUOTES_APPROVE)) {
            return false;
        }

        return $quote->canBeApproved();
    }

    /**
     * Determine whether the user can reject the quote.
     */
    public function reject(User $user, Quote $quote): bool
    {
        if (!$user->can(Permissions::QUOTES_APPROVE)) {
            return false;
        }

        return $quote->canBeRejected();
    }
}
