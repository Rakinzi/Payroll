<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        // Must have view permission
        if (!$user->hasPermissionTo('view employees')) {
            return false;
        }

        // Super admin can view all
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        // Check center access
        if (!$user->canAccessCenter($employee->center_id)) {
            return false;
        }

        // Employee role can only view their own data
        if ($user->hasRole('employee')) {
            return $user->employee_id === $employee->id;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create employees');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        // Must have edit permission
        if (!$user->hasPermissionTo('edit employees')) {
            return false;
        }

        // Super admin can edit all
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        // Check center access
        return $user->canAccessCenter($employee->center_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Employee $employee): bool
    {
        // Must have delete permission
        if (!$user->hasPermissionTo('delete employees')) {
            return false;
        }

        // Super admin can delete all
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        // Check center access
        return $user->canAccessCenter($employee->center_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Employee $employee): bool
    {
        // Must have edit permission to restore
        if (!$user->hasPermissionTo('edit employees')) {
            return false;
        }

        // Super admin can restore all
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        // Check center access
        return $user->canAccessCenter($employee->center_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        // Must have delete permission and be super admin
        return $user->hasPermissionTo('delete employees') &&
               $user->hasPermissionTo('access all centers');
    }
}
