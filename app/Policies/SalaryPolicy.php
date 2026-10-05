<?php

namespace App\Policies;

use App\Models\Salary;
use App\Models\User;

class SalaryPolicy
{
    // View Any
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    // View
    public function view(User $user, Salary $salary): bool
    {
        return $user->isSuperAdmin();
    }

    // Create
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    // Update
    public function update(User $user, Salary $salary): bool
    {
        return $user->isSuperAdmin()
            && $salary->isDraft();
    }

    // Delete
    public function delete(User $user, Salary $salary): bool
    {
        return $user->isSuperAdmin()
            && $salary->isDraft();
    }

    // Delete Any
    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    // Mark As Paid
    public function markAsPaid(User $user, Salary $salary): bool
    {
        return $user->isSuperAdmin()
            && $salary->isDraft();
    }
}
