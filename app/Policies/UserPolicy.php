<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    // View Any

    public function viewAny(User $user): bool
    {
        return $this->canManageEmployees($user);
    }

    // View

    private function canManageEmployees(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true,
        );
    }

    // Create

    public function view(User $user, User $model): bool
    {
        return $this->canManageEmployees($user);
    }

    // Update

    public function create(User $user): bool
    {
        return $this->canManageEmployees($user);
    }

    // Delete

    public function update(User $user, User $model): bool
    {
        return $this->canManageEmployees($user);
    }

    // Restore

    public function delete(User $user, User $model): bool
    {
        return $this->canManageEmployees($user)
            && ! $model->isSuperAdmin();
    }

    // Force Delete

    public function restore(User $user, User $model): bool
    {
        return $this->canManageEmployees($user);
    }

    // Delete Any

    public function forceDelete(User $user, User $model): bool
    {
        return $this->canManageEmployees($user)
            && ! $model->isSuperAdmin();
    }

    // Helpers

    public function deleteAny(User $user): bool
    {
        return $this->canManageEmployees($user);
    }
}
