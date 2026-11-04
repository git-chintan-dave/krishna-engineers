<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->role->canManageCompany()
            || (
                in_array($user->role, [
                    UserRole::OPERATOR,
                    UserRole::BANDSAW,
                    UserRole::DEBURRING_PACKAGING,
                ], true)
                && $attendance->user_id === $user->id
            );
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    /**
     * Determine whether the user can update the attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->role->canManageCompany();
    }

    /**
     * Determine whether the user can delete the attendance record.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->role->canManageCompany();
    }

    /**
     * Determine whether the user can delete multiple attendance records.
     */
    public function deleteAny(User $user): bool
    {
        return $user->role->canManageCompany();
    }

    /**
     * Determine whether the user can import attendance records.
     */
    public function import(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
