<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    /**
     * Determine whether the user can view any feedbacks.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the feedback.
     */
    public function view(User $user, Feedback $feedback): bool
    {
        if ($user->is_system_admin) {
            return true;
        }

        return $feedback->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can create feedbacks.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the feedback.
     */
    public function update(User $user, Feedback $feedback): bool
    {
        if ($user->is_system_admin) {
            return true;
        }

        return $feedback->tenant_id === $user->tenant_id && $feedback->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the feedback.
     * Deletions cannot be performed from the client/tenant app.
     * Only system administrators from the system management panel may delete feedbacks.
     */
    public function delete(User $user, Feedback $feedback): bool
    {
        return false;
    }
}
