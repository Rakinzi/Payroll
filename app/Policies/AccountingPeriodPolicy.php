<?php

namespace App\Policies;

use App\Models\AccountingPeriod;
use App\Models\User;

class AccountingPeriodPolicy
{
    /**
     * Determine if the user can view any accounting periods.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view periods
        return true;
    }

    /**
     * Determine if the user can view the accounting period.
     */
    public function view(User $user, AccountingPeriod $period): bool
    {
        // All authenticated users can view specific periods
        return true;
    }

    /**
     * Determine if the user can run the period.
     */
    public function run(User $user, AccountingPeriod $period): bool
    {
        return $period->canBeRunBy($user);
    }

    /**
     * Determine if the user can refresh the period.
     */
    public function refresh(User $user, AccountingPeriod $period): bool
    {
        return $period->canBeRefreshedBy($user);
    }

    /**
     * Determine if the user can close the period.
     */
    public function close(User $user, AccountingPeriod $period): bool
    {
        return $period->canBeClosedBy($user);
    }

    /**
     * Determine if the user can generate periods.
     */
    public function generatePeriods(User $user): bool
    {
        // Only admins can generate periods
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can export period data.
     */
    public function export(User $user, AccountingPeriod $period): bool
    {
        // Admins can export, or users can export their own center's data
        return $user->hasRole('admin') ||
               $period->centerStatuses()
                      ->where('center_id', $user->center_id)
                      ->exists();
    }
}
