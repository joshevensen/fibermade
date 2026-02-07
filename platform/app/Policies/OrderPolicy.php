<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
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
    public function view(User $user, Order $order): bool
    {
        return $this->isAdmin($user) || $this->belongsToAccount($user, $order->account_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * TODO: Re-enable when ready to work on orders.
     */
    public function create(User $user): bool
    {
        // Restore original logic below when ready
        // return $this->isAdmin($user) || $user->account_id !== null;
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * TODO: Re-enable when ready to work on orders.
     */
    public function update(User $user, Order $order): bool
    {
        // Restore original logic below when ready
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $order->account_id);
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * TODO: Re-enable when ready to work on orders.
     */
    public function delete(User $user, Order $order): bool
    {
        // Restore original logic below when ready
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $order->account_id);
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * TODO: Re-enable when ready to work on orders.
     */
    public function restore(User $user, Order $order): bool
    {
        // Restore original logic below when ready
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $order->account_id);
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * TODO: Re-enable when ready to work on orders.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // Restore original logic below when ready
        // return $this->isAdmin($user) || $this->belongsToAccount($user, $order->account_id);
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
