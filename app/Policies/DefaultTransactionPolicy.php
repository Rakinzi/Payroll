<?php

namespace App\Policies;

use App\Models\DefaultTransaction;
use App\Models\User;

class DefaultTransactionPolicy
{
    /**
     * Determine if the user can view any default transactions.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view default transactions
        return true;
    }

    /**
     * Determine if the user can view the default transaction.
     */
    public function view(User $user, DefaultTransaction $transaction): bool
    {
        // User can view if they're an admin or if it's their center's transaction
        return $user->hasRole('admin') || $transaction->center_id === $user->center_id;
    }

    /**
     * Determine if the user can create default transactions.
     */
    public function create(User $user): bool
    {
        // Only non-admin users (center users) can create default transactions
        return !$user->hasRole('admin') && $user->center_id !== '0';
    }

    /**
     * Determine if the user can update the default transaction.
     */
    public function update(User $user, DefaultTransaction $transaction): bool
    {
        return $transaction->canBeModifiedBy($user);
    }

    /**
     * Determine if the user can delete the default transaction.
     */
    public function delete(User $user, DefaultTransaction $transaction): bool
    {
        return $transaction->canBeModifiedBy($user);
    }
}
