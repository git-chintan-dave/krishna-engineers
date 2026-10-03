<?php

namespace App\Http\Requests\Salaries;

use App\Models\Salary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Salary|null $salary */
        $salary = $this->route('salary');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'salary_period_start' => [
                'required',
                'date',
            ],

            'salary_period_end' => [
                'required',
                'date',
                'after_or_equal:salary_period_start',
            ],

            'upad' => [
                'required',
                'numeric',
                'min:0',
            ],

            'paid_loan' => [
                'required',
                'numeric',
                'min:0',
            ],

            'adjustment' => [
                'required',
                'numeric',
            ],

            'adjustment_reason' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(
                    fn () => (int) $this->input('adjustment') !== 0,
                ),
            ],

            'status' => [
                'nullable',
                Rule::in(['draft', 'paid']),
            ],
        ];
    }
}
