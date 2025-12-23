<?php

namespace App\Policies;

use App\Models\QuoteLine;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteLinePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the quote line.
     * CRITICAL: Prevents modification of quote lines after conversion.
     */
    public function update(User $user, QuoteLine $line): bool
    {
        // Load quote relationship if not loaded
        if (!$line->relationLoaded('quote')) {
            $line->load('quote');
        }

        // Cannot edit lines from converted quotes
        if ($line->quote && $line->quote->isConverted()) {
            return false;
        }

        // User must have update permission and quote must be editable
        return $user->can('quotes.update') && $line->quote?->canBeEdited();
    }

    /**
     * Determine whether the user can delete the quote line.
     * CRITICAL: Prevents deletion of quote lines after conversion.
     */
    public function delete(User $user, QuoteLine $line): bool
    {
        // Load quote relationship if not loaded
        if (!$line->relationLoaded('quote')) {
            $line->load('quote');
        }

        // Cannot delete lines from converted quotes
        if ($line->quote && $line->quote->isConverted()) {
            return false;
        }

        // User must have update permission and quote must be editable
        return $user->can('quotes.update') && $line->quote?->canBeEdited();
    }
}
