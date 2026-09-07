<?php

namespace App\Policies;

use App\Models\BusinessClaim;
use App\Models\User;

class BusinessClaimPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasStaffAccess();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessClaim $businessClaim): bool
    {
        return $user->hasStaffAccess() || $businessClaim->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return ! $user->suspended_at && (bool) $user->mobile_verified_at;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BusinessClaim $businessClaim): bool
    {
        return $user->hasStaffAccess() && $businessClaim->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessClaim $businessClaim): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BusinessClaim $businessClaim): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BusinessClaim $businessClaim): bool
    {
        return false;
    }
}
