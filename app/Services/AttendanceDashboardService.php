<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceDashboardService
{
    private const array EMPLOYEE_ROLES = [
        'operator',
        'bandsaw',
        'deburring_packaging',
    ];

    /**
     * Dashboard data for super_admin and supervisor.
     *
     * Today and yesterday are separate date-based summaries.
     */
    public function managementDashboard(): array
    {
        $today = CarbonImmutable::today();
        $yesterday = $today->subDay();

        return [
            'today' => $this->managementPeriod($today),
            'yesterday' => $this->managementPeriod($yesterday),
        ];
    }

    /**
     * Dashboard data for the authenticated employee only.
     */
    public function employeeDashboard(int $userId): array
    {
        $today = CarbonImmutable::today();

        return [
            'today' => $this->employeeToday($userId, $today),
            'currentPeriod' => $this->employeeMonth(
                $userId,
                $today->startOfMonth(),
                $today,
            ),
            'previousPeriod' => $this->employeeMonth(
                $userId,
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->startOfMonth()->subDay(),
            ),
        ];
    }

    /**
     * Management dashboard summary for one date.
     */
    private function managementPeriod(CarbonImmutable $date): array
    {
        $isWorkingDay = !$date->isSunday();

        $employees = $isWorkingDay
            ? $this->employees()
            : collect();

        $attendanceRecords = $isWorkingDay
            ? DB::table('attendances')
                ->whereDate('attendance_date', $date->toDateString())
                ->whereIn('user_id', $employees->pluck('id'))
                ->get()
                ->keyBy('user_id')
            : collect();

        $items = $employees->map(function ($employee) use ($attendanceRecords) {
            $attendance = $attendanceRecords->get($employee->id);

            return $this->managementEmployeeRow($employee, $attendance);
        });

        $present = $items->where('status', 'present')->count();
        $incomplete = $items->where('status', 'incomplete')->count();
        $leave = $items->where('status', 'leave')->count();

        $attended = $present + $incomplete;

        $averageWorkingSeconds = $attended > 0
            ? (int) round(
                $items->sum('effectiveWorkingSeconds') / $attended
            )
            : 0;

        $averageBreakSeconds = $attended > 0
            ? (int) round($items->sum('breakSeconds') / $attended)
            : 0;

        $totalUndertimeSeconds = (int) $items->sum('undertimeSeconds');

        return [
            'date' => $date->format('d M Y'),
            'label' => $date->format('d F Y'),
            'isWorkingDay' => $isWorkingDay,
            'totalEmployees' => $employees->count(),
            'present' => $present,
            'leave' => $leave,
            'incomplete' => $incomplete,
            'attendancePercentage' => $employees->isNotEmpty()
                ? round(($attended / $employees->count()) * 100, 1)
                : 0,
            'attendancePercentageFormatted' => $employees->isNotEmpty()
                ? number_format(($attended / $employees->count()) * 100, 1) . '%'
                : '0.0%',
            'averageWorkingSeconds' => $averageWorkingSeconds,
            'averageWorkingFormatted' => $this->formatDuration($averageWorkingSeconds),
            'averageBreakSeconds' => $averageBreakSeconds,
            'averageBreakFormatted' => $this->formatDuration($averageBreakSeconds),
            'totalUndertimeSeconds' => $totalUndertimeSeconds,
            'totalUndertimeFormatted' => $this->formatDuration($totalUndertimeSeconds),
            'employees' => $items->values()->all(),
        ];
    }

    /**
     * Build one employee row for the management dashboard.
     */
    private function managementEmployeeRow(object $employee, ?object $attendance): array
    {
        $status = match (true) {
            $attendance === null => 'leave',
            $attendance->end_at === null => 'incomplete',
            default => 'present',
        };

        $workingSeconds = (int) ($attendance->total_working_seconds ?? 0);
        $breakSeconds = (int) ($attendance->break_seconds ?? 0);
        $effectiveSeconds = (int) ($attendance->effective_working_seconds ?? 0);
        $undertimeSeconds = (int) ($attendance->undertime_seconds ?? 0);

        return [
            'id' => (int) $employee->id,
            'name' => $employee->name,
            'employeeCode' => $employee->employee_code,
            'role' => $this->formatRole($employee->role),
            'shift' => $this->formatShift($employee->shift),
            'status' => $status,
            'startAt' => $attendance?->start_at
                ? CarbonImmutable::parse($attendance->start_at)->format('h:i A')
                : null,
            'endAt' => $attendance?->end_at
                ? CarbonImmutable::parse($attendance->end_at)->format('h:i A')
                : null,
            'workingSeconds' => $workingSeconds,
            'workingFormatted' => $this->formatDuration($workingSeconds),
            'breakSeconds' => $breakSeconds,
            'breakFormatted' => $this->formatDuration($breakSeconds),
            'effectiveWorkingSeconds' => $effectiveSeconds,
            'effectiveWorkingFormatted' => $this->formatDuration($effectiveSeconds),
            'undertimeSeconds' => $undertimeSeconds,
            'undertimeFormatted' => $this->formatDuration($undertimeSeconds),
        ];
    }

    /**
     * Today's attendance summary for the logged-in employee.
     */
    private function employeeToday(int $userId, CarbonImmutable $date): array
    {
        $attendance = $date->isSunday()
            ? null
            : DB::table('attendances')
                ->where('user_id', $userId)
                ->whereDate('attendance_date', $date->toDateString())
                ->first();

        $status = match (true) {
            $date->isSunday() => 'weekly_off',
            $attendance === null => 'leave',
            $attendance->end_at === null => 'incomplete',
            default => 'present',
        };

        $workingSeconds = (int) ($attendance->total_working_seconds ?? 0);
        $breakSeconds = (int) ($attendance->break_seconds ?? 0);
        $effectiveSeconds = (int) ($attendance->effective_working_seconds ?? 0);

        return [
            'date' => $date->format('l, d F Y'),
            'status' => $status,
            'statusLabel' => $this->formatAttendanceStatus($status),
            'startAt' => $attendance?->start_at
                ? CarbonImmutable::parse($attendance->start_at)->format('h:i A')
                : '—',
            'endAt' => $attendance?->end_at
                ? CarbonImmutable::parse($attendance->end_at)->format('h:i A')
                : '—',
            'workingSeconds' => $workingSeconds,
            'workingFormatted' => $this->formatDuration($workingSeconds),
            'breakSeconds' => $breakSeconds,
            'breakFormatted' => $this->formatDuration($breakSeconds),
            'effectiveWorkingSeconds' => $effectiveSeconds,
            'effectiveWorkingFormatted' => $this->formatDuration($effectiveSeconds),
            'isWorking' => $attendance !== null && $attendance->end_at === null,
        ];
    }

    /**
     * Monthly attendance summary and daily history for one employee.
     *
     * Missing Monday-Saturday records are included as Leave.
     * Sundays are excluded.
     */
    private function employeeMonth(
        int $userId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $records = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('attendance_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->get()
            ->keyBy(fn (object $record) => CarbonImmutable::parse(
                $record->attendance_date
            )->toDateString());

        $history = collect();

        for ($date = $end; $date->greaterThanOrEqualTo($start); $date = $date->subDay()) {
            if ($date->isSunday()) {
                continue;
            }

            $attendance = $records->get($date->toDateString());

            $status = match (true) {
                $attendance === null => 'leave',
                $attendance->end_at === null => 'incomplete',
                default => 'present',
            };

            $effectiveSeconds = (int) ($attendance->effective_working_seconds ?? 0);
            $breakSeconds = (int) ($attendance->break_seconds ?? 0);
            $undertimeSeconds = (int) ($attendance->undertime_seconds ?? 0);

            $history->push([
                'date' => $date->format('d M Y'),
                'day' => $date->format('l'),
                'clockIn' => $attendance?->start_at
                    ? CarbonImmutable::parse($attendance->start_at)->format('h:i A')
                    : '—',
                'clockOut' => $attendance?->end_at
                    ? CarbonImmutable::parse($attendance->end_at)->format('h:i A')
                    : '—',
                'effectiveWorkingSeconds' => $effectiveSeconds,
                'effectiveWorkingFormatted' => $this->formatDuration($effectiveSeconds),
                'breakSeconds' => $breakSeconds,
                'breakFormatted' => $this->formatDuration($breakSeconds),
                'undertimeSeconds' => $undertimeSeconds,
                'undertimeFormatted' => $this->formatDuration($undertimeSeconds),
                'status' => $status,
                'statusLabel' => $this->formatAttendanceStatus($status),
            ]);
        }

        $workingDays = $history->count();
        $presentDays = $history->where('status', 'present')->count();
        $incompleteDays = $history->where('status', 'incomplete')->count();
        $leaveDays = $history->where('status', 'leave')->count();

        $attendedDays = $presentDays + $incompleteDays;

        $totalUndertimeSeconds = (int) $history->sum('undertimeSeconds');

        $attendanceRate = $workingDays > 0
            ? round(($attendedDays / $workingDays) * 100)
            : 0;

        return [
            'label' => $start->format('F Y'),
            'workingDays' => $workingDays,
            'presentDays' => $presentDays,
            'absentDays' => $leaveDays,
            'incompleteDays' => $incompleteDays,
            'attendedDays' => $attendedDays,
            'attendanceRate' => $attendanceRate,
            'attendanceRateFormatted' => $attendanceRate . '%',
            'totalUndertimeSeconds' => $totalUndertimeSeconds,
            'totalUndertimeFormatted' => $this->formatDuration($totalUndertimeSeconds),
            'records' => $history->values()->all(),
        ];
    }

    /**
     * Employees included in management attendance.
     */
    private function employees(): Collection
    {
        return DB::table('users')
            ->select([
                'id',
                'name',
                'employee_code',
                'role',
                'shift',
            ])
            ->whereIn('role', self::EMPLOYEE_ROLES)
            ->orderBy('name')
            ->get();
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%dh %02dm', $hours, $minutes);
    }

    private function formatRole(?string $role): string
    {
        return match ($role) {
            'operator' => 'Operator',
            'bandsaw' => 'Bandsaw',
            'deburring_packaging' => 'Deburring & Packaging',
            default => str($role ?? '')->replace('_', ' ')->title()->toString(),
        };
    }

    private function formatShift(?string $shift): string
    {
        return match ($shift) {
            'day' => 'Day',
            'night' => 'Night',
            '8_hr' => '8 Hr',
            '8_hr_total' => '8 Hr Total',
            default => str($shift ?? '')->replace('_', ' ')->title()->toString(),
        };
    }

    private function formatAttendanceStatus(string $status): string
    {
        return match ($status) {
            'present' => 'Present',
            'incomplete' => 'Incomplete',
            'weekly_off' => 'Weekly Off',
            default => 'Leave',
        };
    }
}
