<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;

class AccountPolicy
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
    public function view(User $user, Account $account): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $account);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Account $account): bool
    {
        return $this->isAdmin($user) || $this->isAccountOwner($user, $account);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Account $account): bool
    {
        return $this->isAdmin($user) || $this->isAccountOwner($user, $account);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Account $account): bool
    {
        return $this->isAdmin($user) || $this->isAccountOwner($user, $account);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Account $account): bool
    {
        return $this->isAdmin($user) || $this->isAccountOwner($user, $account);
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
    private function belongsToAccount(User $user, Account $account): bool
    {
        return $user->account_id === $account->id;
    }

    /**
     * Check if the user is the owner of the account.
     */
    private function isAccountOwner(User $user, Account $account): bool
    {
        return $user->account_id === $account->id && $user->role === UserRole::Owner;
    }
}
