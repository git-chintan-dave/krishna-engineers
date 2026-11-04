<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tool;
use App\Models\User;

class ToolPolicy
{
    /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    public function view(User $user, Tool $tool): bool
    {
        return $user->role->canManageCompany();
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(User $user, Tool $tool): bool
    {
        return $user->role->canManageCompany();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(User $user, Tool $tool): bool
    {
        return $user->role->canManageCompany();
    }

    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }
}
