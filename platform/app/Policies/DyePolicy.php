<?php

namespace App\Policies;

use App\Models\Dye;
use App\Models\User;

class DyePolicy
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
    public function view(User $user, Dye $dye): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $dye->account_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->account_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dye $dye): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $dye->account_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dye $dye): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $dye->account_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dye $dye): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $dye->account_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dye $dye): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $dye->account_id);
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
