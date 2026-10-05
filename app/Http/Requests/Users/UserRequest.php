<?php

namespace App\Http\Requests\Users;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('post')) {
            return $this->user()?->can('create', User::class) ?? false;
        }

        return $this->user()?->can('update', $this->route('employee')) ?? false;
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
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
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
                'required',
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
                'decimal:0,2',
            ],

            'incentive_per_day' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'deduction_per_day' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            // Account Status
            'status' => [
                'required',
                Rule::enum(Status::class),
            ],
        ];
    }
}
