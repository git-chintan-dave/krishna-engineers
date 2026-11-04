<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SectionSize;
use App\Models\User;

class SectionSizePolicy
{
    /*
    |--------------------------------------------------------------------------
    | View Any
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */

    public function view(User $user, SectionSize $sectionSize): bool
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

    public function update(User $user, SectionSize $sectionSize): bool
    {
        return $user->role->canManageCompany();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(User $user, SectionSize $sectionSize): bool
    {
        return $user->role->canManageCompany();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Any
    |--------------------------------------------------------------------------
    */

    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }
}
