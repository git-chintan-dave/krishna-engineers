<?php

namespace App\Http\Requests\Salaries;

use App\Enums\SalaryStatus;
use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSalaryRequest extends FormRequest
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
                'salary_period_start',
                'salary_period_end',
                'working_days',
                'base_salary',
                'incentive',
                'deduction',
                'final_payable',
                'status',
                'user.name',
                'created_at',
            ]),

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'status' => [
                'nullable',
                Rule::enum(SalaryStatus::class),
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }
}
