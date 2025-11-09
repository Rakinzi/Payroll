<?php

namespace App\Policies;

use App\Models\UserProfile;
use App\Models\User;

class ProfilePolicy
{
    /**
     * Determine if the user can view their profile.
     */
    public function view(User $user): bool
    {
        // All authenticated users can view their own profile
        return true;
    }

    /**
     * Determine if the user can update their profile.
     */
    public function update(User $user, UserProfile $profile): bool
    {
        // Users can only update their own profile
        return $user->id === $profile->user_id;
    }

    /**
     * Determine if the user can update avatar.
     */
    public function updateAvatar(User $user, UserProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    /**
     * Determine if the user can update signature.
     */
    public function updateSignature(User $user, UserProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    /**
     * Determine if the user can update password.
     */
    public function updatePassword(User $user): bool
    {
        // All authenticated users can update their own password
        return true;
    }

    /**
     * Determine if the user can update payslip password.
     */
    public function updatePayslipPassword(User $user): bool
    {
        // All authenticated users can update their own payslip password
        return true;
    }
}
