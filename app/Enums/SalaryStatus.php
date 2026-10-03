<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SalaryStatus: string
{
    use HasOptions;

    case DRAFT = 'draft';
    case PAID = 'paid';

    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PAID => 'Paid',
        };
    }
}
