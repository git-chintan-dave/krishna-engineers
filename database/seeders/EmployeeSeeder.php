<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed the application's initial administrator.
     */
    public function run(): void
    {
        User::updateOrCreate(
            [
                'employee_code' => '002',
            ],
            [
                'name' => 'Chintan Dave',
                'phone_number' => '9714529564',
                'email' => 'connect.chintan.dave@gmail.com',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPERVISOR->value,
                'shift' => null,
                'salary' => 0,
                'incentive_per_day' => 0,
                'deduction_per_day' => 0,
                'status' => Status::ACTIVE->value,
            ],
        );

        User::updateOrCreate(
            [
                'employee_code' => '003',
            ],
            [
                'name' => 'SANJAY',
                'phone_number' => '1234567890',
                'email' => null,
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATOR->value,
                'shift' => UserShift::DAY->value,
                'salary' => 30000,
                'incentive_per_day' => 0,
                'deduction_per_day' => 0,
                'status' => Status::ACTIVE->value,
            ],
        );

        User::updateOrCreate(
            [
                'employee_code' => '004',
            ],
            [
                'name' => 'CHETAN',
                'phone_number' => '1234567891',
                'email' => null,
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATOR->value,
                'shift' => UserShift::NIGHT->value,
                'salary' => 15000,
                'incentive_per_day' => 0,
                'deduction_per_day' => 0,
                'status' => Status::ACTIVE->value,
            ],
        );

        User::updateOrCreate(
            [
                'employee_code' => '005',
            ],
            [
                'name' => 'AMIT',
                'phone_number' => '1234567892',
                'email' => null,
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATOR->value,
                'shift' => UserShift::DAY->value,
                'salary' => 15000,
                'incentive_per_day' => 0,
                'deduction_per_day' => 0,
                'status' => Status::ACTIVE->value,
            ],
        );

        User::updateOrCreate(
            [
                'employee_code' => '006',
            ],
            [
                'name' => 'PALLAVI',
                'phone_number' => '1234567893',
                'email' => null,
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATOR->value,
                'shift' => UserShift::DAY->value,
                'salary' => 20000,
                'incentive_per_day' => 0,
                'deduction_per_day' => 0,
                'status' => Status::ACTIVE->value,
            ],
        );
    }
}
