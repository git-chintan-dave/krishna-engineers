<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BandsawWorkLog;
use App\Models\User;

class BandsawWorkLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageCompany() || $user->role === UserRole::BANDSAW;
    }

    public function view(
        User $user,
        BandsawWorkLog $bandsawWorkLog,
    ): bool {
        return $user->role->canManageCompany() || $user->role === UserRole::BANDSAW;
    }

    public function create(User $user): bool
    {
        return $user->role->canManageCompany() || $user->role === UserRole::BANDSAW;
    }

    public function update(
        User $user,
        BandsawWorkLog $bandsawWorkLog,
    ): bool {
        if ($user->role->canManageCompany()) {
            return true;
        }

        return $user->role === UserRole::OPERATOR && $bandsawWorkLog->user_id === $user->id;
    }

    public function delete(
        User $user,
        BandsawWorkLog $bandsawWorkLog,
    ): bool {
        if ($user->role->canManageCompany()) {
            return true;
        }

        return $user->role === UserRole::OPERATOR && $bandsawWorkLog->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }
}
