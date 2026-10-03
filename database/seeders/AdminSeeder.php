<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's initial administrator.
     */
    public function run(): void
    {
        User::updateOrCreate(
            [
                'employee_code' => '001',
            ],
            [
                'name' => 'Dipesh Panchal',
                'phone_number' => '9016712007',
                'email' => 'krishnaengineers2010@gmail.com',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPER_ADMIN->value,
                'email_verified_at' => now(),
                'status' => Status::ACTIVE->value,
            ],
        );
    }
}
