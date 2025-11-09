<?php

namespace App\Policies;

use App\Models\CustomTransaction;
use App\Models\User;

class CustomTransactionPolicy
{
    /**
     * Determine if the user can view any custom transactions.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view custom transactions
        return true;
    }

    /**
     * Determine if the user can view the custom transaction.
     */
    public function view(User $user, CustomTransaction $transaction): bool
    {
        // User can view if they're an admin or if it's their center's transaction
        return $user->hasRole('admin') || $transaction->center_id === $user->center_id;
    }

    /**
     * Determine if the user can create custom transactions.
     */
    public function create(User $user): bool
    {
        // Only non-admin users (center users) can create custom transactions
        return !$user->hasRole('admin') && $user->center_id !== '0';
    }

    /**
     * Determine if the user can update the custom transaction.
     */
    public function update(User $user, CustomTransaction $transaction): bool
    {
        return $transaction->canBeModifiedBy($user);
    }

    /**
     * Determine if the user can delete the custom transaction.
     */
    public function delete(User $user, CustomTransaction $transaction): bool
    {
        return $transaction->canBeModifiedBy($user);
    }
}
