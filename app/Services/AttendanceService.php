<?php

namespace App\Services;

use App\Enums\SalaryStatus;
use App\Exceptions\AttendanceActionException;
use App\Exceptions\AttendanceImportException;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceService
{
    /*
    |--------------------------------------------------------------------------
    | Live Attendance
    |--------------------------------------------------------------------------
    */

    public function start(
        User $user,
        ?CarbonInterface $at = null,
    ): Attendance {
        $at ??= now();

        return DB::transaction(
            function () use ($user, $at): Attendance {
                $attendanceDate = $at->toDateString();

                $this->ensureDateEditable(
                    $user->id,
                    $attendanceDate,
                );

                $attendance = $user->attendances()
                    ->whereDate(
                        'attendance_date',
                        $attendanceDate,
                    )
                    ->first();

                if ($attendance?->start_at !== null) {
                    throw new RuntimeException(
                        'Attendance has already been started for today.',
                    );
                }

                if ($attendance) {
                    $this->ensureEditable(
                        $attendance,
                    );

                    $attendance->update([
                        'start_at' => $at,
                        'end_at' => null,
                    ]);

                    return $attendance->fresh();
                }

                return $user->attendances()->create([
                    'attendance_date' => $attendanceDate,
                    'start_at' => $at,
                ]);
            },
        );
    }

    private function ensureDateEditable(
        int $userId,
        string|CarbonInterface $attendanceDate,
    ): void {
        if (
            ! $this->isDateLocked(
                $userId,
                $attendanceDate,
            )
        ) {
            return;
        }

        $employee = User::query()
            ->select([
                'id',
                'name',
                'employee_code',
            ])
            ->find($userId);

        $employeeLabel = $employee?->employee_code
            ? "{$employee->name} ({$employee->employee_code})"
            : ($employee?->name ?? 'Employee');

        $date = $attendanceDate instanceof CarbonInterface
            ? $attendanceDate->toDateString()
            : $this->normalizeDate($attendanceDate);

        throw new RuntimeException(
            "Attendance for {$employeeLabel} on "
            .Carbon::parse($date)->format('d M Y')
            .' cannot be changed because the salary for this '
            .'period has already been paid.',
        );
    }

    private function isDateLocked(
        int $userId,
        string|CarbonInterface $attendanceDate,
    ): bool {
        $date = $attendanceDate instanceof CarbonInterface
            ? $attendanceDate->toDateString()
            : $this->normalizeDate($attendanceDate);

        return Salary::query()
            ->where(
                'user_id',
                $userId,
            )
            ->where(
                'status',
                SalaryStatus::PAID,
            )
            ->whereDate(
                'salary_period_start',
                '<=',
                $date,
            )
            ->whereDate(
                'salary_period_end',
                '>=',
                $date,
            )
            ->exists();
    }

    private function normalizeDate(
        string|CarbonInterface $date,
    ): string {
        return $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();
    }

    public function ensureEditable(
        Attendance $attendance,
    ): void {
        if (! $this->isLocked($attendance)) {
            return;
        }

        $employeeLabel = $attendance->user?->employee_code
            ? "{$attendance->user->name} "
            ."({$attendance->user->employee_code})"
            : 'This employee';

        $date = $attendance->attendance_date
            ->format('d M Y');

        throw new AttendanceActionException(
            "Attendance for {$employeeLabel} on "
            .Carbon::parse($date)->format('d M Y')
            .' cannot be changed because the salary for this '
            .'period has already been paid.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function isLocked(
        Attendance $attendance,
    ): bool {
        return $this->isDateLocked(
            $attendance->user_id,
            $attendance->attendance_date,
        );
    }

    public function update(
        Attendance $attendance,
        array $data,
        array $breaks = [],
    ): Attendance {
        $this->ensureEditable(
            $attendance,
        );

        return DB::transaction(
            function () use (
                $attendance,
                $data,
                $breaks,
            ): Attendance {
                $userId = isset($data['user_id'])
                    ? (int) $data['user_id']
                    : $attendance->user_id;

                $attendanceDate = isset(
                    $data['attendance_date'],
                )
                    ? $this->normalizeDate(
                        $data['attendance_date'],
                    )
                    : $attendance->attendance_date->toDateString();

                /*
                 * Check the target employee/date.
                 *
                 * This is important when editing an attendance
                 * and changing either employee or date.
                 */
                $this->ensureDateEditable(
                    $userId,
                    $attendanceDate,
                );

                $this->ensureAttendanceDoesNotExist(
                    $userId,
                    $attendanceDate,
                    $attendance,
                );

                $attendance->update([
                    ...$data,
                    'attendance_date' => $attendanceDate,
                ]);

                $attendance = $attendance->fresh();

                $this->syncBreaks(
                    $attendance,
                    $breaks,
                );

                return $this->recalculateTotals(
                    $attendance->fresh(),
                );
            },
        );
    }

    private function ensureAttendanceDoesNotExist(
        int $userId,
        string|CarbonInterface $attendanceDate,
        ?Attendance $ignore = null,
    ): void {
        $date = $attendanceDate instanceof CarbonInterface
            ? $attendanceDate->toDateString()
            : $this->normalizeDate($attendanceDate);

        $exists = Attendance::query()
            ->where(
                'user_id',
                $userId,
            )
            ->whereDate(
                'attendance_date',
                $date,
            )
            ->when(
                $ignore,
                fn ($query) => $query->where(
                    $ignore->getKeyName(),
                    '!=',
                    $ignore->getKey(),
                ),
            )
            ->exists();

        if (! $exists) {
            return;
        }

        $employee = User::query()
            ->select([
                'id',
                'name',
                'employee_code',
            ])
            ->find($userId);

        $employeeLabel = $employee?->employee_code
            ? "{$employee->name} ({$employee->employee_code})"
            : ($employee?->name ?? 'Employee');

        throw new AttendanceActionException(
            "Attendance already exists for {$employeeLabel} on "
            .Carbon::parse($date)->format('d M Y')
            .'.',
        );
    }

    private function syncBreaks(
        Attendance $attendance,
        array $breaks,
    ): void {
        $attendance->breaks()->delete();

        foreach ($breaks as $break) {
            $startAt = $break['start_at'] instanceof CarbonInterface
                ? $break['start_at']
                : Carbon::parse(
                    $break['start_at'],
                );

            $endAt = empty($break['end_at'])
                ? null
                : (
                    $break['end_at'] instanceof CarbonInterface
                        ? $break['end_at']
                        : Carbon::parse(
                            $break['end_at'],
                        )
                );

            if (
                $endAt !== null
                && $endAt->lt($startAt)
            ) {
                throw new RuntimeException(
                    'Break end time cannot be before break start time.',
                );
            }

            $durationSeconds = $endAt !== null
                ? max(
                    0,
                    (int) $startAt->diffInSeconds(
                        $endAt,
                    ),
                )
                : 0;

            $attendance->breaks()->create([
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration_seconds' => $durationSeconds,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    |
    | Import never updates existing attendance.
    |
    | Duplicate employee/date records are rejected.
    |
    */

    public function delete(
        Attendance $attendance,
    ): void {
        $this->ensureEditable(
            $attendance,
        );

        DB::transaction(
            fn () => $attendance->delete(),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calculations
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        array $breaks = [],
    ): Attendance {
        return DB::transaction(
            function () use ($data, $breaks): Attendance {
                $userId = (int) $data['user_id'];

                $attendanceDate = $this->normalizeDate(
                    $data['attendance_date'],
                );

                $this->ensureDateEditable(
                    $userId,
                    $attendanceDate,
                );

                $this->ensureAttendanceDoesNotExist(
                    $userId,
                    $attendanceDate,
                );

                $attendance = Attendance::create([
                    ...$data,
                    'attendance_date' => $attendanceDate,
                    'total_working_seconds' => 0,
                    'break_seconds' => 0,
                    'effective_working_seconds' => 0,
                    'undertime_seconds' => 0,
                ]);

                $this->syncBreaks(
                    $attendance,
                    $breaks,
                );

                return $this->recalculateTotals(
                    $attendance,
                );
            },
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Salary Lock
    |--------------------------------------------------------------------------
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

        $breakSeconds = max(3600, $breakSeconds);
        $standardShiftSeconds = 12 * 3600; // 12 Hours shift
        $effectiveWorkingSeconds = max(0, ($totalWorkingSeconds - $breakSeconds));
        $undertimeSeconds = max(0, ($standardShiftSeconds - $totalWorkingSeconds));

        $attendance->update([
            'total_working_seconds' => $totalWorkingSeconds,
            'break_seconds' => $breakSeconds,
            'effective_working_seconds' => $effectiveWorkingSeconds,
            'undertime_seconds' => $undertimeSeconds,
        ]);

        return $attendance->fresh();
    }

    private function calculateTotalWorkingSeconds(
        mixed $startAt,
        mixed $endAt,
    ): int {
        if (
            $startAt === null
            || $endAt === null
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
            (int) $startAt->diffInSeconds(
                $endAt,
            ),
        );
    }

    public function startBreak(
        Attendance $attendance,
        ?CarbonInterface $at = null,
    ): AttendanceBreak {
        $at ??= now();

        if ($attendance->start_at === null) {
            throw new RuntimeException(
                'Attendance has not been started.',
            );
        }

        if ($attendance->end_at !== null) {
            throw new RuntimeException(
                'Attendance has already been completed.',
            );
        }

        if (
            $attendance->breaks()
                ->whereNull('end_at')
                ->exists()
        ) {
            throw new RuntimeException(
                'A break is already active.',
            );
        }

        $this->ensureEditable(
            $attendance,
        );

        if ($at->lt($attendance->start_at)) {
            throw new RuntimeException(
                'Break start time cannot be before attendance start time.',
            );
        }

        return $attendance->breaks()->create([
            'start_at' => $at,
        ]);
    }

    public function endBreak(
        Attendance $attendance,
        ?CarbonInterface $at = null,
    ): AttendanceBreak {
        $at ??= now();

        $this->ensureEditable(
            $attendance,
        );

        $break = $attendance->breaks()
            ->whereNull('end_at')
            ->latest('start_at')
            ->first();

        if (! $break) {
            throw new RuntimeException(
                'There is no active break.',
            );
        }

        if ($at->lt($break->start_at)) {
            throw new RuntimeException(
                'Break end time cannot be before break start time.',
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

    /*
    |--------------------------------------------------------------------------
    | Duplicate Attendance
    |--------------------------------------------------------------------------
    */

    public function complete(
        Attendance $attendance,
        ?CarbonInterface $at = null,
    ): Attendance {
        $at ??= now();

        if ($attendance->start_at === null) {
            throw new RuntimeException(
                'Attendance has not been started.',
            );
        }

        if ($attendance->end_at !== null) {
            throw new RuntimeException(
                'Attendance has already been completed.',
            );
        }

        if (
            $attendance->breaks()
                ->whereNull('end_at')
                ->exists()
        ) {
            throw new RuntimeException(
                'Please end the active break before completing attendance.',
            );
        }

        $this->ensureEditable(
            $attendance,
        );

        if ($at->lt($attendance->start_at)) {
            throw new RuntimeException(
                'Attendance end time cannot be before start time.',
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

    /*
    |--------------------------------------------------------------------------
    | Breaks
    |--------------------------------------------------------------------------
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
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function deleteMany(
        array $ids,
    ): int {
        return DB::transaction(
            function () use ($ids): int {
                $attendances = Attendance::query()
                    ->whereIn('id', $ids)
                    ->get();

                /*
                 * Check every record before deleting anything.
                 *
                 * If even one record is locked, nothing is deleted.
                 */
                foreach ($attendances as $attendance) {
                    $this->ensureEditable(
                        $attendance,
                    );
                }

                $deleted = 0;

                foreach ($attendances as $attendance) {
                    if ($attendance->delete()) {
                        $deleted++;
                    }
                }

                return $deleted;
            },
        );
    }

    public function import(
        User $user,
        string $attendanceDate,
        CarbonInterface $startAt,
        ?CarbonInterface $endAt = null,
        array $breaks = [],
    ): Attendance {
        return DB::transaction(
            function () use (
                $user,
                $attendanceDate,
                $startAt,
                $endAt,
                $breaks,
            ): Attendance {
                $attendanceDate = $this->normalizeDate(
                    $attendanceDate,
                );

                /*
                 * Import-specific salary lock message.
                 */
                try {
                    $this->ensureDateEditable(
                        $user->id,
                        $attendanceDate,
                    );
                } catch (RuntimeException $exception) {
                    throw new AttendanceImportException(
                        "Attendance for {$user->name} "
                        ."({$user->employee_code}) on "
                        .Carbon::parse($attendanceDate)->format('d M Y')
                        .' cannot be imported because the salary '
                        .'for this period has already been paid.',
                        previous: $exception,
                    );
                }

                /*
                 * Import-specific duplicate message.
                 */
                try {
                    $this->ensureAttendanceDoesNotExist(
                        $user->id,
                        $attendanceDate,
                    );
                } catch (RuntimeException $exception) {
                    throw new AttendanceImportException(
                        $exception->getMessage(),
                        previous: $exception,
                    );
                }

                if (
                    $endAt !== null
                    && $endAt->lt($startAt)
                ) {
                    throw new AttendanceImportException(
                        "Attendance for {$user->name} "
                        ."({$user->employee_code}) on "
                        .Carbon::parse($attendanceDate)->format('d M Y')
                        .' has an invalid clock-out time.',
                    );
                }

                $attendance = $user->attendances()->create([
                    'attendance_date' => $attendanceDate,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                ]);

                $this->syncBreaks(
                    $attendance,
                    $breaks,
                );

                return $this->recalculateTotals(
                    $attendance->fresh(),
                );
            },
        );
    }
}
