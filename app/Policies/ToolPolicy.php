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
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function view(User $user, Tool $tool): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(User $user, Tool $tool): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(User $user, Tool $tool): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
