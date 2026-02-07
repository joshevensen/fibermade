<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->account_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $customer->account_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     */
    public function create(User $user): bool
    {
        // Stage 2: Restore original logic below
        // return $this->isAdmin($user) || $user->account_id !== null;
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     */
    public function update(User $user, Customer $customer): bool
    {
        // Stage 2: Restore original logic below
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $customer->account_id);
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // Stage 2: Restore original logic below
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $customer->account_id);
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     */
    public function restore(User $user, Customer $customer): bool
    {
        // Stage 2: Restore original logic below
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $customer->account_id);
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        // Stage 2: Restore original logic below
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $customer->account_id);
        return false;
    }

    /**
     * Check if the user is an admin.
     */
    private function isAdmin(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Check if the user belongs to the account.
     */
    private function belongsToAccount(User $user, int $accountId): bool
    {
        return $user->account_id === $accountId;
    }
}
