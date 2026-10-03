<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Machine;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        Machine::query()->upsert(
            [
                [
                    'name' => 'PX-30',
                    'machine_number' => 'VMC-1',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'COSMOS-1',
                    'machine_number' => 'VMC-2',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'COSMOS-2',
                    'machine_number' => 'VMC-3',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'TRIDENT',
                    'machine_number' => 'VMC-4',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'AKIRA-1',
                    'machine_number' => 'VMC-5',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'AKIRA-2',
                    'machine_number' => 'VMC-6',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'FEELER',
                    'machine_number' => 'VMC-7',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'PX-20 - 1',
                    'machine_number' => 'VMC-8',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'PX-20 - 2',
                    'machine_number' => 'VMC-9',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'TRIDENT-2',
                    'machine_number' => 'VMC-10',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'LOKESH',
                    'machine_number' => 'VMC-11',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'COSMOS-3',
                    'machine_number' => 'VMC-12',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'COSMOS-4',
                    'machine_number' => 'VMC-13',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'TOPWELL',
                    'machine_number' => 'VMC-14',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'SURYA',
                    'machine_number' => 'VMC-15',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'ACE',
                    'machine_number' => 'TR-1',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'DX-170',
                    'machine_number' => 'TR-2',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'DX-200-5A',
                    'machine_number' => 'TR-3',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'DX-200-3A-1',
                    'machine_number' => 'TR-4',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'DX-200-3A-2',
                    'machine_number' => 'TR-5',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'DX-200-3A-3',
                    'machine_number' => 'TR-6',
                    'status' => Status::ACTIVE->value,
                ],
                [
                    'name' => 'DX-200-3A-4',
                    'machine_number' => 'TR-7',
                    'status' => Status::ACTIVE->value,
                ],
            ],
            ['machine_number'],
            ['name', 'status'],
        );
    }
}
