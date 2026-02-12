<?php

namespace App\Policies;

use App\Enums\AccountType;
use App\Models\Creator;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can see all stores
        // Creators can see stores they have relationships with
        // Store accounts can see their own store
        return $user->is_admin
            || $user->account?->type === AccountType::Creator
            || $user->account?->type === AccountType::Store;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Store $store): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Store accounts can view their own store
        if ($user->account_id === $store->account_id) {
            return true;
        }

        // Creators can view stores they have vendor relationships with
        if ($user->account?->type === AccountType::Creator && $user->account->creator) {
            $storeIds = $user->account->creator->stores()->pluck('stores.id');

            return $storeIds->contains($store->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only creators or admins can create stores (typically during registration)
        // Store accounts shouldn't create other stores
        return $user->is_admin || $user->account?->type === AccountType::Creator;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Store $store): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Store accounts can update their own store
        return $user->account_id === $store->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Store $store): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Store accounts can delete their own store
        return $user->account_id === $store->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Store $store): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Store accounts can restore their own store
        return $user->account_id === $store->account_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Store $store): bool
    {
        // Only admins can permanently delete
        return $user->is_admin;
    }

    /**
     * Determine whether the store can view the creator's orders (order list page).
     */
    public function viewCreatorOrders(User $user, Store $store, Creator $creator): bool
    {
        return $store->creators()->where('creator_id', $creator->id)->exists();
    }
}
