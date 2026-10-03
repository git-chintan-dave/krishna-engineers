<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    /**
     * Determine whether the user can update the attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    /**
     * Determine whether the user can delete the attendance record.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine whether the user can delete multiple attendance records.
     */
    public function deleteAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    private function isSuperAdmin(User $user): bool
    {
        return $user->role?->value === 'super_admin'
            || $user->role === 'super_admin';
    }

    private function isSupervisor(User $user): bool
    {
        return $user->role?->value === 'supervisor'
            || $user->role === 'supervisor';
    }
}
