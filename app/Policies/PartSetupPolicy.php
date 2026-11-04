<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PartSetup;
use App\Models\User;

class PartSetupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    public function view(User $user, PartSetup $partSetup): bool
    {
        return $user->role->canManageCompany();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    public function update(User $user, PartSetup $partSetup): bool
    {
        return $user->role->canManageCompany();
    }

    public function delete(User $user, PartSetup $partSetup): bool
    {
        return $user->role->canManageCompany();
    }

    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }
}
