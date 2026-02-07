<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
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
    public function view(User $user, Media $media): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $accountId = $this->getAccountIdFromMediable($media);

        if ($accountId === null) {
            return false;
        }

        return $this->belongsToAccount($user, $accountId);
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
    public function update(User $user, Media $media): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $accountId = $this->getAccountIdFromMediable($media);

        if ($accountId === null) {
            return false;
        }

        return $this->belongsToAccount($user, $accountId);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $accountId = $this->getAccountIdFromMediable($media);

        if ($accountId === null) {
            return false;
        }

        return $this->belongsToAccount($user, $accountId);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Media $media): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $accountId = $this->getAccountIdFromMediable($media);

        if ($accountId === null) {
            return false;
        }

        return $this->belongsToAccount($user, $accountId);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $accountId = $this->getAccountIdFromMediable($media);

        if ($accountId === null) {
            return false;
        }

        return $this->belongsToAccount($user, $accountId);
    }

    /**
     * Get the account ID from the mediable model.
     */
    private function getAccountIdFromMediable(Media $media): ?int
    {
        $media->loadMissing('mediable');

        if ($media->mediable === null) {
            return null;
        }

        // Check if the mediable model has an account_id property
        if (isset($media->mediable->account_id)) {
            return $media->mediable->account_id;
        }

        return null;
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
