<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ToolTransaction;
use App\Models\User;

class ToolTransactionPolicy
{
    public function view(
        User            $user,
        ToolTransaction $toolTransaction,
    ): bool
    {
        return $this->viewAny($user);
    }

    public function viewAny(User $user): bool
    {
        return in_array(
            $user->role,
            [UserRole::SUPER_ADMIN, UserRole::SUPERVISOR],
            true,
        );
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(
        User            $user,
        ToolTransaction $toolTransaction,
    ): bool
    {
        return $this->update($user, $toolTransaction);
    }

    public function update(
        User            $user,
        ToolTransaction $toolTransaction,
    ): bool
    {
        return $user->role === UserRole::SUPER_ADMIN
            || (
                $user->role === UserRole::SUPERVISOR
                && $toolTransaction->created_by_user_id === $user->id
            );
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }
}
