<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OperatorWorkLog;
use App\Models\User;

class OperatorWorkLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageCompany() || $user->role === UserRole::OPERATOR;
    }

    public function view(
        User $user,
        OperatorWorkLog $operatorWorkLog
    ): bool {
        if ($user->role->canManageCompany()) {
            return true;
        }

        return $user->role === UserRole::OPERATOR && $operatorWorkLog->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role->canManageCompany() || $user->role === UserRole::OPERATOR;
    }

    public function update(
        User $user,
        OperatorWorkLog $operatorWorkLog
    ): bool {
        if ($user->role->canManageCompany()) {
            return true;
        }

        return $user->role === UserRole::OPERATOR && $operatorWorkLog->user_id === $user->id;
    }

    public function delete(
        User $user,
        OperatorWorkLog $operatorWorkLog
    ): bool {
        if ($user->role->canManageCompany()) {
            return true;
        }

        return $user->role === UserRole::OPERATOR && $operatorWorkLog->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }
}
