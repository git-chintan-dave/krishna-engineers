<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Setup;
use App\Models\User;

class SetupPolicy
{
    /**
     * Determine whether the user can view the setup listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the setup.
     */
    public function view(User $user, Setup $setup): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can create setups.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the setup.
     */
    public function update(User $user, Setup $setup): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the setup.
     */
    public function delete(User $user, Setup $setup): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete multiple setups.
     */
    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
