<?php

namespace App\Http\Requests\Salaries;

use Illuminate\Foundation\Http\FormRequest;

class SalaryPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
        ];
    }
}
