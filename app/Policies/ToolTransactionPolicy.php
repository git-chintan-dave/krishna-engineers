<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ToolTransaction;
use App\Models\User;

class ToolTransactionPolicy
{
    /*
    |--------------------------------------------------------------------------
    | View Any
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */

    public function view(
        User $user,
        ToolTransaction $toolTransaction
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

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        ToolTransaction $toolTransaction
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(
        User $user,
        ToolTransaction $toolTransaction
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Any
    |--------------------------------------------------------------------------
    */

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
