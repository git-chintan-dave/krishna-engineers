<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BandsawWorkLog;
use App\Models\User;

class BandsawWorkLogPolicy
{
    /**
     * Determine whether the user can view the Bandsaw work log listing.
     */
    public function viewAny(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::BANDSAW,
            ],
            true
        );
    }

    /**
     * Determine whether the user can view the Bandsaw work log.
     */
    public function view(
        User $user,
        BandsawWorkLog $bandsawWorkLog
    ): bool {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::BANDSAW,
            ],
            true
        );
    }

    /**
     * Determine whether the user can create Bandsaw work logs.
     */
    public function create(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::BANDSAW,
            ],
            true
        );
    }

    /**
     * Determine whether the user can update the Bandsaw work log.
     */
    public function update(
        User $user,
        BandsawWorkLog $bandsawWorkLog
    ): bool {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::BANDSAW,
            ],
            true
        );
    }

    /**
     * Determine whether the user can delete the Bandsaw work log.
     */
    public function delete(
        User $user,
        BandsawWorkLog $bandsawWorkLog
    ): bool {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::BANDSAW,
            ],
            true
        );
    }

    /**
     * Determine whether the user can delete multiple Bandsaw work logs.
     */
    public function deleteAny(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true
        );
    }
}
