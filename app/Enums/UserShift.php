<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserShift: string
{
    use HasOptions;

    case DAY = 'day';
    case NIGHT = 'night';
    case EIGHT_HOURS = '8_hr';
    case EIGHT_HOURS_TOTAL = '8_hr_total';

    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    public function label(): string
    {
        return match ($this) {
            self::DAY => 'Day',
            self::NIGHT => 'Night',
            self::EIGHT_HOURS => '8 HR',
            self::EIGHT_HOURS_TOTAL => '8 HR TOTAL',
        };
    }
}
