<?php

namespace App\Http\Requests\Users;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    use IndexQueryRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->indexQueryRules([
                'name',
                'employee_code',
                'phone_number',
                'email',
                'role',
                'shift',
                'salary',
                'incentive_per_day',
                'deduction_per_day',
                'status',
                'created_at',
            ]),

            'role' => [
                'nullable',
                'array',
            ],
            'role.*' => [
                Rule::enum(UserRole::class),
            ],

            'shift' => [
                'nullable',
                'array'
            ],
            'shift.*' => [
                Rule::enum(UserShift::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(Status::class),
            ],
        ];
    }
}
