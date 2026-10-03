<?php

namespace App\Http\Requests\Users;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            // Employee Information
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'employee_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'employee_code')
                    ->ignore($employee),
            ],

            'phone_number' => [
                'nullable',
                'digits:10',
                Rule::unique('users', 'phone_number')
                    ->ignore($employee),
            ],

            'alternate_phone_number' => [
                'nullable',
                'digits:10',
                Rule::unique('users', 'alternate_phone_number')
                    ->ignore($employee),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($employee),
            ],

            'joining_date' => [
                'nullable',
                'date',
            ],

            'aadhar_number' => [
                'nullable',
                'digits:12',
                Rule::unique('users', 'aadhar_number')
                    ->ignore($employee),
            ],

            // Authentication
            'password' => [
                $this->isMethod('post')
                    ? 'required'
                    : 'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            // Employee Configuration
            'role' => [
                'nullable',
                Rule::enum(UserRole::class),
            ],

            'shift' => [
                'nullable',
                Rule::enum(UserShift::class),
            ],

            // Salary Configuration
            'salary' => [
                'required',
                'numeric',
                'min:0',
            ],

            'incentive_per_day' => [
                'required',
                'numeric',
                'min:0',
            ],

            'deduction_per_day' => [
                'required',
                'numeric',
                'min:0',
            ],

            // Account Status
            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
