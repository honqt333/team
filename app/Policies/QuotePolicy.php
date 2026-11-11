<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Access\HandlesAuthorization;

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
     * The quote must belong to the user's tenant and to the branch they
     * are currently working in. Multi-branch users switch their current
     * center; EnsureCenterContext validates that the pivot allows it.
     */
    private function belongsToCurrentContext(User $user, Quote $quote): bool
    {
        return $quote->tenant_id === $user->tenant_id
            && (int) $quote->center_id === (int) $user->current_center_id;
    }

    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): bool
    {
        return $user->can(Permissions::QUOTES_VIEW)
            && $this->belongsToCurrentContext($user, $quote);
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
        if (! $user->can(Permissions::QUOTES_UPDATE)) {
            return false;
        }

        // Can only update draft or sent quotes in the current branch
        return $this->belongsToCurrentContext($user, $quote)
            && $quote->canBeEdited();
    }

    /**
     * Determine whether the user can delete the quote.
     */
    public function delete(User $user, Quote $quote): bool
    {
        if (! $user->can(Permissions::QUOTES_DELETE)) {
            return false;
        }

        // Can only delete draft quotes in the current branch
        return $this->belongsToCurrentContext($user, $quote)
            && $quote->isDraft();
    }

    /**
     * Determine whether the user can approve the quote.
     */
    public function approve(User $user, Quote $quote): bool
    {
        if (! $user->can(Permissions::QUOTES_APPROVE)) {
            return false;
        }

        return $this->belongsToCurrentContext($user, $quote)
            && $quote->canBeApproved();
    }

    /**
     * Determine whether the user can reject the quote.
     */
    public function reject(User $user, Quote $quote): bool
    {
        if (! $user->can(Permissions::QUOTES_APPROVE)) {
            return false;
        }

        return $this->belongsToCurrentContext($user, $quote)
            && $quote->canBeRejected();
    }
}
