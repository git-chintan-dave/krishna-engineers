<?php

namespace App\Policies;

use App\Models\Salary;
use App\Models\User;

class SalaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    public function view(User $user, Salary $salary): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    public function update(User $user, Salary $salary): bool
    {
        if ($salary->isPaid()) {
            return false;
        }

        return $this->isSuperAdmin($user)
            || $this->isSupervisor($user);
    }

    public function delete(User $user, Salary $salary): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function markAsPaid(User $user, Salary $salary): bool
    {
        if ($salary->isPaid()) {
            return false;
        }

        return $this->isSuperAdmin($user);
    }

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
