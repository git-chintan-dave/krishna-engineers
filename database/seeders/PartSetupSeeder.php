<?php

namespace Database\Seeders;

use App\Models\Part;
use App\Models\PartSetup;
use App\Models\Setup;
use Illuminate\Database\Seeder;

class PartSetupSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        $parts = Part::query()
            ->whereIn('name', [
                '2H-2000M',
                '2I-2003',
                '2X-2013',
                '1P-4000',
                'RM-197',
                '2I-2004',
                '2I-2005',
            ])
            ->get()
            ->keyBy('name');

        $setups = Setup::query()
            ->whereIn('name', [
                'S1',
                'S2',
                'S3',
                'S4',
                'S5',
                'S6',
                'DR/FC',
                'DRILL',
                'OUTER',
                'KNURLING',
            ])
            ->get()
            ->keyBy('name');

        foreach ([
            [
                'part_id' => 1,
                'setup_id' => 11,
                'image' => null,
                'machine_cycle_time_seconds' => 840,
                'actual_cycle_time_seconds' => 840,
            ],
            [
                'part_id' => 2,
                'setup_id' => 2,
                'image' => null,
                'machine_cycle_time_seconds' => 240,
                'actual_cycle_time_seconds' => 240,
            ],
            [
                'part_id' => 3,
                'setup_id' => 4,
                'image' => null,
                'machine_cycle_time_seconds' => 1200,
                'actual_cycle_time_seconds' => 1200,
            ],
            [
                'part_id' => 4,
                'setup_id' => 1,
                'image' => null,
                'machine_cycle_time_seconds' => 600,
                'actual_cycle_time_seconds' => 600,
            ],
            [
                'part_id' => 4,
                'setup_id' => 2,
                'image' => null,
                'machine_cycle_time_seconds' => 600,
                'actual_cycle_time_seconds' => 600,
            ],
            [
                'part_id' => 5,
                'setup_id' => 11,
                'image' => null,
                'machine_cycle_time_seconds' => 300,
                'actual_cycle_time_seconds' => 300,
            ],
            [
                'part_id' => 6,
                'setup_id' => 3,
                'image' => null,
                'machine_cycle_time_seconds' => 900,
                'actual_cycle_time_seconds' => 900,
            ],
            [
                'part_id' => $parts['2H-2000M']->id,
                'setup_id' => $setups['S1']->id,
                'image' => null,
                'machine_cycle_time_seconds' => 3600,
                'actual_cycle_time_seconds' => 3900,
            ],
            [
                'part_id' => $parts['2H-2000M']->id,
                'setup_id' => $setups['S2']->id,
                'image' => null,
                'machine_cycle_time_seconds' => 4200,
                'actual_cycle_time_seconds' => 4500,
            ],
            [
                'part_id' => $parts['2I-2003']->id,
                'setup_id' => $setups['S1']->id,
                'image' => null,
                'machine_cycle_time_seconds' => 3000,
                'actual_cycle_time_seconds' => 3300,
            ],
            [
                'part_id' => $parts['2X-2013']->id,
                'setup_id' => $setups['S1']->id,
                'image' => null,
                'machine_cycle_time_seconds' => 2700,
                'actual_cycle_time_seconds' => 3000,
            ],
            [
                'part_id' => $parts['1P-4000']->id,
                'setup_id' => $setups['S2']->id,
                'image' => null,
                'machine_cycle_time_seconds' => 4800,
                'actual_cycle_time_seconds' => 5100,
            ],
            [
                'part_id' => $parts['RM-197']->id,
                'setup_id' => $setups['S1']->id,
                'image' => null,
                'machine_cycle_time_seconds' => 2400,
                'actual_cycle_time_seconds' => 2700,
            ],
        ] as $partSetup) {
            PartSetup::create($partSetup);
        }
    }
}
