<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Part;
use App\Models\User;

class PartPolicy
{
    /**
     * Determine whether the user can view the part listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the part.
     */
    public function view(User $user, Part $part): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can create parts.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the part.
     */
    public function update(User $user, Part $part): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the part.
     */
    public function delete(User $user, Part $part): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete multiple parts.
     */
    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
