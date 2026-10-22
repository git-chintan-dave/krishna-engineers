<?php

namespace App\Http\Requests\Attendances;

use App\Http\Requests\Concerns\IndexQueryRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAttendanceRequest extends FormRequest
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
                'attendance_date',
                'start_at',
                'end_at',
                'break_seconds',
                'effective_working_seconds',
            ]),

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::excludeIf(
                    ! in_array(
                        $this->user()?->role?->value ?? $this->user()?->role,
                        ['super_admin', 'supervisor'],
                        true,
                    ),
                ),
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
