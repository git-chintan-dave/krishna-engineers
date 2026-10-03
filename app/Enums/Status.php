<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Status: string
{
    use HasOptions;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}
