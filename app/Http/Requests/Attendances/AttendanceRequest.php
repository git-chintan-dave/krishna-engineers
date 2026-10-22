<?php

namespace App\Http\Requests\Attendances;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

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
                'required',
                'date',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cross-field validation
    |--------------------------------------------------------------------------
    */

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /*
             * Do not run custom date/time validation when the basic
             * validation has already failed.
             */
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $attendanceDateInput = $this->input('attendance_date');
            $startAtInput = $this->input('start_at');
            $endAtInput = $this->input('end_at');
            $breaks = $this->input('breaks', []);

            if (
                blank($attendanceDateInput)
                || blank($startAtInput)
            ) {
                return;
            }

            try {
                $attendanceDate = Carbon::parse(
                    $attendanceDateInput,
                )->startOfDay();

                $startAt = Carbon::parse($startAtInput);

                $endAt = filled($endAtInput)
                    ? Carbon::parse($endAtInput)
                    : null;
            } catch (Throwable) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 1. Clock-in date must match attendance date
            |--------------------------------------------------------------------------
            */

            if (! $startAt->isSameDay($attendanceDate)) {
                $validator->errors()->add(
                    'start_at',
                    'Clock-in date must match the attendance date.',
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Clock-out validation
            |--------------------------------------------------------------------------
            |
            | Allowed:
            |
            | Attendance: 06 Oct
            | Start:      06 Oct 08:00 PM
            | End:        06 Oct 11:00 PM   ✓
            |
            | Attendance: 06 Oct
            | Start:      06 Oct 08:00 PM
            | End:        07 Oct 05:00 AM   ✓
            |
            | Not allowed:
            |
            | End:        08 Oct 05:00 AM   ✗
            |
            */

            if ($endAt !== null) {
                $sameDay = $endAt->isSameDay($attendanceDate);
                $nextDay = $endAt->isSameDay(
                    $attendanceDate->copy()->addDay(),
                );

                if (! $sameDay && ! $nextDay) {
                    $validator->errors()->add(
                        'end_at',
                        'Clock-out date must be the same as or the next day after the attendance date.',
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Clock-out must be after clock-in
                |--------------------------------------------------------------------------
                */

                if ($endAt->lt($startAt)) {
                    $validator->errors()->add(
                        'end_at',
                        'Clock-out time must be the same as or later than clock-in time.',
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Break validation
            |--------------------------------------------------------------------------
            */

            $parsedBreaks = [];

            foreach ($breaks as $index => $break) {
                $breakStartInput = $break['start_at'] ?? null;
                $breakEndInput = $break['end_at'] ?? null;

                if (
                    blank($breakStartInput)
                    || blank($breakEndInput)
                ) {
                    continue;
                }

                try {
                    $breakStart = Carbon::parse($breakStartInput);
                    $breakEnd = Carbon::parse($breakEndInput);
                } catch (Throwable) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Break must have a valid duration
                |--------------------------------------------------------------------------
                */

                if ($breakEnd->lt($breakStart)) {
                    $validator->errors()->add(
                        "breaks.{$index}.end_at",
                        'Break end time must be the same as or later than break start time.',
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Break must be within attendance start/end
                |--------------------------------------------------------------------------
                */

                if ($breakStart->lt($startAt)) {
                    $validator->errors()->add(
                        "breaks.{$index}.start_at",
                        'Break start time cannot be before clock-in time.',
                    );
                }

                if ($breakEnd->lt($startAt)) {
                    $validator->errors()->add(
                        "breaks.{$index}.end_at",
                        'Break end time cannot be before clock-in time.',
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | If attendance has been completed, break must end before
                | or exactly at clock-out.
                |--------------------------------------------------------------------------
                */

                if ($endAt !== null) {
                    if ($breakStart->gt($endAt)) {
                        $validator->errors()->add(
                            "breaks.{$index}.start_at",
                            'Break start time cannot be after clock-out time.',
                        );
                    }

                    if ($breakEnd->gt($endAt)) {
                        $validator->errors()->add(
                            "breaks.{$index}.end_at",
                            'Break end time cannot be after clock-out time.',
                        );
                    }
                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | No clock-out means attendance is still open.
                    | For manual create/edit, completed breaks require an
                    | attendance end time so the complete interval is known.
                    |--------------------------------------------------------------------------
                    */

                    $validator->errors()->add(
                        "breaks.{$index}.end_at",
                        'Clock-out time is required when adding a break.',
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Break must be on attendance date or next date only
                |--------------------------------------------------------------------------
                |
                | This is naturally constrained by start/end below, but we
                | explicitly validate it for a clearer error.
                |--------------------------------------------------------------------------
                */

                $breakStartAllowedDate =
                    $breakStart->isSameDay($attendanceDate)
                    || $breakStart->isSameDay(
                        $attendanceDate->copy()->addDay(),
                    );

                $breakEndAllowedDate =
                    $breakEnd->isSameDay($attendanceDate)
                    || $breakEnd->isSameDay(
                        $attendanceDate->copy()->addDay(),
                    );

                if (! $breakStartAllowedDate) {
                    $validator->errors()->add(
                        "breaks.{$index}.start_at",
                        'Break start date must be the attendance date or the next day.',
                    );
                }

                if (! $breakEndAllowedDate) {
                    $validator->errors()->add(
                        "breaks.{$index}.end_at",
                        'Break end date must be the attendance date or the next day.',
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Store valid parsed break for overlap checking
                |--------------------------------------------------------------------------
                */

                $parsedBreaks[] = [
                    'index' => $index,
                    'start_at' => $breakStart,
                    'end_at' => $breakEnd,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Break overlap validation
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | 10:00 - 10:30
            | 10:20 - 10:45   ✗
            |
            | Touching is allowed:
            |
            | 10:00 - 10:30
            | 10:30 - 11:00   ✓
            |--------------------------------------------------------------------------
            */

            usort(
                $parsedBreaks,
                fn (array $a, array $b): int => $a['start_at']->getTimestamp()
                    <=> $b['start_at']->getTimestamp(),
            );

            $previousBreak = null;

            foreach ($parsedBreaks as $break) {
                if (
                    $previousBreak !== null
                    && $break['start_at']->lt(
                        $previousBreak['end_at'],
                    )
                ) {
                    $validator->errors()->add(
                        "breaks.{$break['index']}.start_at",
                        'This break overlaps with another break.',
                    );
                }

                if (
                    $previousBreak === null
                    || $break['end_at']->gt(
                        $previousBreak['end_at'],
                    )
                ) {
                    $previousBreak = $break;
                }
            }
        });
    }
}
