<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    /**
     * Determine if the user can view any currencies.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view currencies
        return true;
    }

    /**
     * Determine if the user can view the currency.
     */
    public function view(User $user, Currency $currency): bool
    {
        // All authenticated users can view currencies
        return true;
    }

    /**
     * Determine if the user can create currencies.
     */
    public function create(User $user): bool
    {
        // Only admins can create currencies
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the currency.
     */
    public function update(User $user, Currency $currency): bool
    {
        // Only admins can update currencies
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the currency.
     */
    public function delete(User $user, Currency $currency): bool
    {
        // Only admins can delete currencies
        // Cannot delete base currency (handled in controller)
        return $user->hasRole('admin');
    }
}
