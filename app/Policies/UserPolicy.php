<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    public function view(User $user, User $model): bool
    {
        return $user->role->canManageCompany();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    public function update(User $user, User $model): bool
    {
        return $user->role->canManageCompany();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role->canManageCompany() && ! $model->isSuperAdmin();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->role->canManageCompany();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->role->canManageCompany() && ! $model->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }
}
