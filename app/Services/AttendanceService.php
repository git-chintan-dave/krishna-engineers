<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceService
{
    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    /**
     * Start attendance for a user.
     */
    public function start(
        User $user,
        ?CarbonInterface $at = null,
    ): Attendance {
        $at ??= now();

        return DB::transaction(
            function () use ($user, $at): Attendance {
                $attendance = $user->attendances()
                    ->whereDate(
                        'attendance_date',
                        $at->toDateString(),
                    )
                    ->first();

                if ($attendance?->isStarted()) {
                    throw new RuntimeException(
                        'Attendance has already been started for today.',
                    );
                }

                if ($attendance) {
                    $attendance->update([
                        'start_at' => $at,
                    ]);

                    return $attendance->fresh();
                }

                return $user->attendances()->create([
                    'attendance_date' => $at->toDateString(),
                    'start_at' => $at,
                ]);
            },
        );
    }

    /**
     * Start a break for the current attendance.
     */
    public function startBreak(
        Attendance $attendance,
        ?CarbonInterface $at = null,
    ): AttendanceBreak {
        $at ??= now();

        if (! $attendance->isStarted()) {
            throw new RuntimeException(
                'Attendance has not been started.',
            );
        }

        if ($attendance->isCompleted()) {
            throw new RuntimeException(
                'Attendance has already been completed.',
            );
        }

        if ($attendance->isOnBreak()) {
            throw new RuntimeException(
                'A break is already active.',
            );
        }

        return $attendance->breaks()->create([
            'start_at' => $at,
        ]);
    }

    /**
     * End the current break.
     */
    public function endBreak(
        Attendance $attendance,
        ?CarbonInterface $at = null,
    ): AttendanceBreak {
        $at ??= now();

        $break = $attendance->breaks()
            ->whereNull('end_at')
            ->latest('start_at')
            ->first();

        if (! $break) {
            throw new RuntimeException(
                'There is no active break.',
            );
        }

        $break->update([
            'end_at' => $at,
            'duration_seconds' => max(
                0,
                (int) $break->start_at->diffInSeconds($at),
            ),
        ]);

        $this->recalculateTotals(
            $attendance->fresh(),
        );

        return $break->fresh();
    }

    /**
     * Complete attendance for the day.
     */
    public function complete(
        Attendance $attendance,
        ?CarbonInterface $at = null,
    ): Attendance {
        $at ??= now();

        if (! $attendance->isStarted()) {
            throw new RuntimeException(
                'Attendance has not been started.',
            );
        }

        if ($attendance->isCompleted()) {
            throw new RuntimeException(
                'Attendance has already been completed.',
            );
        }

        if ($attendance->isOnBreak()) {
            throw new RuntimeException(
                'Please end the active break before completing attendance.',
            );
        }

        $attendance->update([
            'end_at' => $at,
        ]);

        $this->recalculateTotals(
            $attendance->fresh(),
        );

        return $attendance->fresh([
            'breaks',
        ]);
    }

    /**
     * Get today's attendance for a user.
     */
    public function today(
        User $user,
    ): ?Attendance {
        return $user->attendances()
            ->with('breaks')
            ->whereDate(
                'attendance_date',
                today(),
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Excel Import
    |--------------------------------------------------------------------------
    */

    private function importRow(
        User $user,
        array $row,
    ): ?array {
        $attendanceDate = $this->parseDate(
            $row[0] ?? null,
        );

        if (! $attendanceDate) {
            return null;
        }

        $startTime = $this->parseTime(
            $row[2] ?? null,
        );

        if ($startTime === null) {
            return null;
        }

        $endTime = $this->parseTime(
            $row[17] ?? null,
        );

        $startAt = $this->combineDateAndTime(
            $attendanceDate,
            $startTime,
        );

        $endAt = $endTime !== null
            ? $this->combineDateAndTime(
                $attendanceDate,
                $endTime,
            )
            : null;

        if (
            $endAt !== null &&
            $endAt->lt($startAt)
        ) {
            $endAt->addDay();
        }

        $breaks = $this->extractBreaks(
            $row,
            $attendanceDate,
        );

        return DB::transaction(
            function () use (
                $user,
                $attendanceDate,
                $startAt,
                $endAt,
                $breaks,
            ): array {
                $attendance = Attendance::query()
                    ->where('user_id', $user->id)
                    ->whereDate(
                        'attendance_date',
                        $attendanceDate,
                    )
                    ->first();

                $created = $attendance === null;

                $attendance ??= new Attendance;

                $attendance->fill([
                    'user_id' => $user->id,
                    'attendance_date' => $attendanceDate,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                ]);

                $attendance->save();

                $attendance->breaks()->delete();

                foreach ($breaks as $break) {
                    $attendance->breaks()->create(
                        $break,
                    );
                }

                $attendance =
                    $this->recalculateTotals(
                        $attendance->fresh(),
                    );

                return [
                    'created' => $created,
                    'attendance' => $attendance,
                ];
            },
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Excel Parsing
    |--------------------------------------------------------------------------
    */

    /**
     * Extract employee code from the report header.
     */
    private function extractEmployeeCode(
        array $rows,
    ): ?string {
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                if (
                    mb_strtolower(
                        trim((string) $value),
                    ) !== 'empcode'
                ) {
                    continue;
                }

                $employeeCode = $row[$index + 1] ?? null;

                if (
                    $employeeCode === null ||
                    trim((string) $employeeCode) === ''
                ) {
                    return null;
                }

                return trim(
                    (string) $employeeCode,
                );
            }
        }

        return null;
    }

    /**
     * Determine whether a row contains an attendance date.
     */
    private function isAttendanceRow(
        array $row,
    ): bool {
        return $this->parseDate(
            $row[0] ?? null,
        ) !== null;
    }

    /**
     * Extract breaks from IN/OUT columns.
     *
     * Excel layout:
     *
     * C  = IN
     * D  = Out1
     * E  = In2
     * F  = Out2
     * G  = In3
     * H  = Out3
     * ...
     * Q  = In8
     * R  = Out
     */
    private function extractBreaks(
        array $row,
        string $attendanceDate,
    ): array {
        $breaks = [];

        /*
        |--------------------------------------------------------------------------
        | Intermediate OUT / IN pairs
        |--------------------------------------------------------------------------
        */

        for ($column = 3; $column <= 16; $column += 2) {
            $outTime = $this->parseTime(
                $row[$column] ?? null,
            );

            $inTime = $this->parseTime(
                $row[$column + 1] ?? null,
            );

            if (
                $outTime === null ||
                $inTime === null
            ) {
                continue;
            }

            $breakStart = $this->combineDateAndTime(
                $attendanceDate,
                $outTime,
            );

            $breakEnd = $this->combineDateAndTime(
                $attendanceDate,
                $inTime,
            );

            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            $durationSeconds = max(
                0,
                (int) $breakStart->diffInSeconds(
                    $breakEnd,
                ),
            );

            $breaks[] = [
                'start_at' => $breakStart,
                'end_at' => $breakEnd,
                'duration_seconds' => $durationSeconds,
            ];
        }

        return $breaks;
    }

    /**
     * Parse an Excel date value.
     */
    private function parseDate(
        mixed $value,
    ): ?string {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(
                \DateTime::createFromInterface($value),
            )->toDateString();
        }

        $value = trim((string) $value);

        try {
            return Carbon::createFromFormat(
                'd/m/Y',
                $value,
            )->toDateString();
        } catch (\Throwable) {
            try {
                return Carbon::parse(
                    $value,
                )->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }
    }

    /**
     * Parse an Excel time value.
     */
    private function parseTime(
        mixed $value,
    ): ?string {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(
                \DateTime::createFromInterface($value),
            )->format('H:i:s');
        }

        $value = trim((string) $value);

        if (
            $value === '--:--' ||
            $value === '00:00'
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Excel time fraction
        |--------------------------------------------------------------------------
        */

        if (
            is_numeric($value) &&
            (float) $value >= 0 &&
            (float) $value < 1
        ) {
            return Carbon::createFromTimestampUTC(
                (int) round(
                    (float) $value * 86400,
                ),
            )->format('H:i:s');
        }

        foreach ([
            'H:i:s',
            'H:i',
            'G:i:s',
            'G:i',
        ] as $format) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value,
                )->format('H:i:s');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Combine attendance date and time.
     */
    private function combineDateAndTime(
        string $date,
        string $time,
    ): Carbon {
        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            "{$date} {$time}",
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * Recalculate attendance totals.
     */
    public function recalculateTotals(
        Attendance $attendance,
    ): Attendance {
        $totalWorkingSeconds =
            $this->calculateTotalWorkingSeconds(
                $attendance->start_at,
                $attendance->end_at,
            );

        $breakSeconds = (int) $attendance
            ->breaks()
            ->sum('duration_seconds');

        $attendance->update([
            'total_working_seconds' => $totalWorkingSeconds,
            'break_seconds' => $breakSeconds,
            'effective_working_seconds' => max(
                0,
                $totalWorkingSeconds -
                $breakSeconds,
            ),
        ]);

        return $attendance->fresh();
    }

    /**
     * Calculate total elapsed working seconds.
     */
    private function calculateTotalWorkingSeconds(
        mixed $startAt,
        mixed $endAt,
    ): int {
        if (
            $startAt === null ||
            $endAt === null
        ) {
            return 0;
        }

        $startAt = $startAt instanceof CarbonInterface
            ? $startAt
            : Carbon::parse($startAt);

        $endAt = $endAt instanceof CarbonInterface
            ? $endAt
            : Carbon::parse($endAt);

        return max(
            0,
            (int) $startAt->diffInSeconds($endAt),
        );
    }
}
