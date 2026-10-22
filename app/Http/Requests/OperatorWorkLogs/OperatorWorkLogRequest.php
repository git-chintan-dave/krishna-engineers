<?php

namespace App\Http\Requests\OperatorWorkLogs;

use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class OperatorWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('work_date')) {
            $this->merge([
                'work_date' => Carbon::parse(
                    $this->input('work_date'),
                )->toDateString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    User::class,
                    'id',
                )->where(
                    'role',
                    UserRole::OPERATOR->value,
                ),
                Rule::requiredIf(
                    fn () => $this->user()->role !== UserRole::OPERATOR
                ),
            ],

            'work_date' => [
                'required',
                'date',
            ],

            'shift' => [
                'nullable',
                Rule::enum(UserShift::class),
            ],

            'machine_id' => [
                'required',
                'integer',
                'exists:machines,id',
            ],

            'part_id' => [
                'required',
                'integer',
                'exists:parts,id',
            ],

            'setup_id' => [
                'required',
                'integer',
                'exists:setups,id',
            ],

            'part_setup_id' => [
                'required',
                'integer',
                'exists:part_setups,id',
            ],

            'machine_cycle_time' => [
                'required',
                'integer',
                'min:1',
            ],

            'actual_cycle_time' => [
                'required',
                'integer',
                'min:1',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'rejection_quantity' => [
                'nullable',
                'integer',
                'min:0',
                'lte:quantity',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
