<?php

namespace App\Policies;

use App\Enums\AccountType;
use App\Models\Creator;
use App\Models\User;

class CreatorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only creators or admins can view creators
        return $user->is_admin || $user->account?->type === AccountType::Creator;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Creator $creator): bool
    {
        // Users can view creators they have relationships with, or their own
        return $user->is_admin || $user->account_id === $creator->account_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only creator account types can create creator records (during onboarding)
        return $user->account?->type === AccountType::Creator;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Creator $creator): bool
    {
        // Users can only update their own creator record
        return $user->is_admin || $user->account_id === $creator->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Creator $creator): bool
    {
        // Users can only delete their own creator record
        return $user->is_admin || $user->account_id === $creator->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Creator $creator): bool
    {
        // Users can only restore their own creator record
        return $user->is_admin || $user->account_id === $creator->account_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Creator $creator): bool
    {
        // Only admins can permanently delete
        return $user->is_admin;
    }
}
