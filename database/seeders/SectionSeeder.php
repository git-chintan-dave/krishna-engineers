<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        Section::query()->upsert(
            [
                ['name' => '121892', 'status' => Status::ACTIVE->value],
                ['name' => '121795', 'status' => Status::ACTIVE->value],
                ['name' => '121893', 'status' => Status::ACTIVE->value],
                ['name' => '121772', 'status' => Status::ACTIVE->value],
                ['name' => 'RE-12000', 'status' => Status::ACTIVE->value],
                ['name' => 'RE-12001', 'status' => Status::ACTIVE->value],
                ['name' => 'RE-10205', 'status' => Status::ACTIVE->value],
                ['name' => 'SP-10887', 'status' => Status::ACTIVE->value],
            ],
            ['name'],
            ['status'],
        );
    }
}
