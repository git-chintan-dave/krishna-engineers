<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ToolTransactionType: string
{
    use HasOptions;

    case OPENING = 'opening';
    case PURCHASE = 'purchase';
    case ISSUE = 'issue';
    case RETURN = 'return';
    case ADJUSTMENT = 'adjustment';

    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    public function label(): string
    {
        return match ($this) {
            self::OPENING => 'Opening',
            self::PURCHASE => 'Purchase',
            self::ISSUE => 'Issue',
            self::RETURN => 'Return',
            self::ADJUSTMENT => 'Adjustment',
        };
    }
}
