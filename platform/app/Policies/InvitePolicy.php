<?php

namespace App\Policies;

use App\Enums\AccountType;
use App\Enums\InviteType;
use App\Models\Invite;
use App\Models\User;

class InvitePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invite $invite): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Only creators or admins may create store invites (and future invite types).
     */
    public function create(User $user): bool
    {
        return $user->is_admin || $user->account?->type === AccountType::Creator;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invite $invite): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invite $invite): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invite $invite): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invite $invite): bool
    {
        return false;
    }

    /**
     * Determine whether the user can resend the invite.
     * Only the inviter (creator) may resend, and the invite must be pending and store-type.
     */
    public function resend(User $user, Invite $invite): bool
    {
        if ($invite->invite_type !== InviteType::Store) {
            return false;
        }

        if (! $invite->isPending()) {
            return false;
        }

        $creator = $user->account?->creator;
        if (! $creator) {
            return false;
        }

        return $invite->inviter_type === $creator->getMorphClass()
            && $invite->inviter_id === $creator->id;
    }
}
