<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OperatorWorkLog;
use App\Models\User;

class OperatorWorkLogPolicy
{
    /**
     * Determine whether the user can view the operator work log listing.
     */
    public function viewAny(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::OPERATOR,
            ],
            true
        );
    }

    /**
     * Determine whether the user can view the operator work log.
     */
    public function view(
        User $user,
        OperatorWorkLog $operatorWorkLog
    ): bool {
        if (in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true
        )) {
            return true;
        }

        return $user->role === UserRole::OPERATOR
            && $operatorWorkLog->user_id === $user->id;
    }

    /**
     * Determine whether the user can create operator work logs.
     */
    public function create(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
                UserRole::OPERATOR,
            ],
            true
        );
    }

    /**
     * Determine whether the user can update the operator work log.
     */
    public function update(
        User $user,
        OperatorWorkLog $operatorWorkLog
    ): bool {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true
        );
    }

    /**
     * Determine whether the user can delete the operator work log.
     */
    public function delete(
        User $user,
        OperatorWorkLog $operatorWorkLog
    ): bool {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true
        );
    }

    /**
     * Determine whether the user can delete multiple operator work logs.
     */
    public function deleteAny(User $user): bool
    {
        return in_array(
            $user->role,
            [
                UserRole::SUPER_ADMIN,
                UserRole::SUPERVISOR,
            ],
            true
        );
    }
}
