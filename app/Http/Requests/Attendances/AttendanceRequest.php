<?php

namespace App\Http\Requests\Attendances;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        /** @var Attendance|null $attendance */
        $attendance = $this->route('attendance');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'attendance_date' => [
                'required',
                'date',
                Rule::unique('attendances', 'attendance_date')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->input('user_id'),
                        ),
                    )
                    ->ignore($attendance),
            ],

            'start_at' => [
                'required',
                'date',
            ],

            'end_at' => [
                'nullable',
                'date',
                'after_or_equal:start_at',
            ],

            'breaks' => [
                'nullable',
                'array',
            ],

            'breaks.*.start_at' => [
                'required',
                'date',
            ],

            'breaks.*.end_at' => [
                'nullable',
                'date',
                'after_or_equal:breaks.*.start_at',
            ],
        ];
    }
}
