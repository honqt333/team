<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quotes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('quotes.view');
    }

    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): bool
    {
        return $user->can('quotes.view');
    }

    /**
     * Determine whether the user can create quotes.
     */
    public function create(User $user): bool
    {
        return $user->can('quotes.create');
    }

    /**
     * Determine whether the user can update the quote.
     */
    public function update(User $user, Quote $quote): bool
    {
        if (!$user->can('quotes.update')) {
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
        if (!$user->can('quotes.delete')) {
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
        if (!$user->can('quotes.approve')) {
            return false;
        }

        return $quote->canBeApproved();
    }

    /**
     * Determine whether the user can reject the quote.
     */
    public function reject(User $user, Quote $quote): bool
    {
        if (!$user->can('quotes.approve')) {
            return false;
        }

        return $quote->canBeRejected();
    }
}
