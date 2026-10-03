<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ToolType: string
{
    use HasOptions;

    case ENDMILL = 'endmill';
    case DRILL = 'drill';
    case INSERT = 'insert';
    case SPECIAL_TOOLS = 'special_tools';

    /*
    |--------------------------------------------------------------------------
    | Label
    |--------------------------------------------------------------------------
    */

    public function label(): string
    {
        return match ($this) {
            self::ENDMILL => 'Endmill',
            self::DRILL => 'Drill',
            self::INSERT => 'Insert',
            self::SPECIAL_TOOLS => 'Special Tools',
        };
    }
}
