<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case SUPER_ADMIN = 'super_admin';
    case SUPERVISOR = 'supervisor';
    case OPERATOR = 'operator';
    case BANDSAW = 'bandsaw';
    case DEBURRING_PACKAGING = 'deburring_packaging';

    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::SUPERVISOR => 'Supervisor',
            self::OPERATOR => 'Operator',
            self::BANDSAW => 'Bandsaw',
            self::DEBURRING_PACKAGING => 'Deburring & Packaging',
        };
    }
}
