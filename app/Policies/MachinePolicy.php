<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Machine;
use App\Models\User;

class MachinePolicy
{
    /**
     * Determine whether the user can view the machine listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the machine.
     */
    public function view(User $user, Machine $machine): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can create machines.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the machine.
     */
    public function update(User $user, Machine $machine): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the machine.
     */
    public function delete(User $user, Machine $machine): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
