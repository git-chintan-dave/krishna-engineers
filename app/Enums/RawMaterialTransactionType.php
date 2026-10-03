<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RawMaterialTransactionType: string
{
    use HasOptions;

    case OPENING = 'opening';
    case PURCHASE = 'purchase';
    case OUTWARD = 'outward';
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
            self::OUTWARD => 'Outward',
            self::ADJUSTMENT => 'Adjustment',
        };
    }
}
