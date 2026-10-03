<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        $this->call([
            MachineSeeder::class,
            SetupSeeder::class,
            PartSeeder::class,
            PartSetupSeeder::class,
            SectionSeeder::class,
            SectionSizeSeeder::class,
            ToolSeeder::class,
        ]);
    }
}
