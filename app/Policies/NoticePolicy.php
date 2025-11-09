<?php

namespace App\Policies;

use App\Models\Notice;
use App\Models\User;

class NoticePolicy
{
    /**
     * Determine if the user can view any notices.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view notices
        return true;
    }

    /**
     * Determine if the user can view the notice.
     */
    public function view(User $user, Notice $notice): bool
    {
        // All authenticated users can view notices
        return true;
    }

    /**
     * Determine if the user can create notices.
     */
    public function create(User $user): bool
    {
        // Only admins and HR users can create notices
        return $user->hasRole('admin') || $user->hasRole('hr');
    }

    /**
     * Determine if the user can update the notice.
     */
    public function update(User $user, Notice $notice): bool
    {
        return $notice->canBeModifiedBy($user);
    }

    /**
     * Determine if the user can delete the notice.
     */
    public function delete(User $user, Notice $notice): bool
    {
        return $notice->canBeModifiedBy($user);
    }

    /**
     * Determine if the user can download the notice.
     */
    public function download(User $user, Notice $notice): bool
    {
        // All authenticated users can download notices
        return true;
    }
}
