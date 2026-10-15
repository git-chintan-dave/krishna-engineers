<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RawMaterialTransaction;
use App\Models\User;

class RawMaterialTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function view(
        User $user,
        RawMaterialTransaction $rawMaterialTransaction,
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function update(
        User $user,
        RawMaterialTransaction $rawMaterialTransaction,
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function delete(
        User $user,
        RawMaterialTransaction $rawMaterialTransaction,
    ): bool {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
