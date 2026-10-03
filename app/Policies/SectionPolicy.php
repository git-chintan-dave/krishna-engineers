<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function view(User $user, Section $section): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function update(User $user, Section $section): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
