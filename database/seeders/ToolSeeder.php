<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\ToolType;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        Tool::query()->upsert(
            [
                [
                    'name' => 'Ø 12 ENDMILL 4 FL',
                    'type' => ToolType::ENDMILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 12 ENDMILL 3 FL',
                    'type' => ToolType::ENDMILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 10 ENDMILL 4 FL',
                    'type' => ToolType::ENDMILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 16 ENDMILL 4 FL',
                    'type' => ToolType::ENDMILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 8 ENDMILL 4 FL',
                    'type' => ToolType::ENDMILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 6 ENDMILL 4 FL',
                    'type' => ToolType::ENDMILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 5.2 DRILL',
                    'type' => ToolType::DRILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 10.25 DRILL',
                    'type' => ToolType::DRILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 6 DRILL',
                    'type' => ToolType::DRILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'Ø 9.5 DRILL',
                    'type' => ToolType::DRILL->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'CCMT 09T304 FG CT3000',
                    'type' => ToolType::INSERT->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'VBMT 160404 FG CT3000',
                    'type' => ToolType::INSERT->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'WNMG 060408 MT TT5080',
                    'type' => ToolType::INSERT->value,
                    'image' => null,
                    'quantity' => 0,
                    'status' => Status::ACTIVE->value,
                ],
            ],
            ['type', 'name'],
            ['image', 'quantity', 'status'],
        );
    }
}
