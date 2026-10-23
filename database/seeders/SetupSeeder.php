<?php

namespace Database\Seeders;

use App\Models\Setup;
use Illuminate\Database\Seeder;

class SetupSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        Setup::query()->upsert(
            [
                ['name' => 'S1'],
                ['name' => 'S2'],
                ['name' => 'S3'],
                ['name' => 'S4'],
                ['name' => 'S5'],
                ['name' => 'S6'],
                ['name' => 'DR/FC'],
                ['name' => 'DRILL'],
                ['name' => 'OUTER'],
                ['name' => 'KNURLING'],
                ['name' => 'FINAL'],
            ],
            ['name'],
        );
    }
}
