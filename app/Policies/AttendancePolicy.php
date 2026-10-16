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
        return $this->isAdminOrSupervisor($user)
            || $this->isOperatorOrBandsaw($user);
    }

    private function isAdminOrSupervisor(User $user): bool
    {
        return $this->hasRole($user, 'super_admin')
            || $this->hasRole($user, 'supervisor');
    }

    private function hasRole(User $user, string $role): bool
    {
        return $user->role?->value === $role
            || $user->role === $role;
    }

    private function isOperatorOrBandsaw(User $user): bool
    {
        return $this->hasRole($user, 'operator')
            || $this->hasRole($user, 'bandsaw');
    }

    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $this->isAdminOrSupervisor($user)
            || (
                $this->isOperatorOrBandsaw($user)
                && $attendance->user_id === $user->id
            );
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $this->isAdminOrSupervisor($user);
    }

    /**
     * Determine whether the user can update the attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $this->isAdminOrSupervisor($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether the user can delete the attendance record.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $this->isAdminOrSupervisor($user);
    }

    /**
     * Determine whether the user can delete multiple attendance records.
     */
    public function deleteAny(User $user): bool
    {
        return $this->isAdminOrSupervisor($user);
    }

    /**
     * Determine whether the user can import attendance records.
     */
    public function import(User $user): bool
    {
        return $this->hasRole($user, 'super_admin');
    }
}
