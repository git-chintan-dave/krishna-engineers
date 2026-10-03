<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RawMaterialTransaction;
use App\Models\User;

class RawMaterialTransactionPolicy
{
    /**
     * Determine whether the user can view the raw material transaction listing.
     */
    public function viewAny(User $user): bool
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

    /**
     * Determine whether the user can view the raw material transaction.
     */
    public function view(
        User $user,
        RawMaterialTransaction $rawMaterialTransaction
    ): bool {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true
        );
    }

    /**
     * Determine whether the user can create raw material transactions.
     */
    public function create(User $user): bool
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

    /**
     * Determine whether the user can update the raw material transaction.
     */
    public function update(
        User $user,
        RawMaterialTransaction $rawMaterialTransaction
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the raw material transaction.
     */
    public function delete(
        User $user,
        RawMaterialTransaction $rawMaterialTransaction
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete multiple raw material transactions.
     */
    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
