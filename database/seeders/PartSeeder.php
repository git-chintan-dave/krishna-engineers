<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Part;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        Part::query()->upsert(
            [
                [
                    'name' => '2H-2000M',
                    'new_part_name' => '2000HHIT',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => '2I-2003',
                    'new_part_name' => '2003IDEN',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => '2X-2013',
                    'new_part_name' => '2013XDEN',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => '1P-4000',
                    'new_part_name' => '40001PTB',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'RM-197',
                    'new_part_name' => '197RMPLY',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => '2I-2004',
                    'new_part_name' => '2004IDEN',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => '2I-2005',
                    'new_part_name' => '2005IDEN',
                    'status' => Status::ACTIVE->value,
                ],
            ],
            ['new_part_name'],
            ['name', 'status'],
        );
    }
}
