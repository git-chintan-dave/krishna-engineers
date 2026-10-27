<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Attendance;
use App\Models\BandsawWorkLog;
use App\Models\OperatorWorkLog;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    private const RECENT_LIMIT = 8;

    public function __construct(
        private readonly OperatorProductionEfficiencyService $efficiencyService,
    ) {}

    /**
     * Return immediately available dashboard metadata.
     */
    public function metadataForUser(User $user): array
    {
        return match ($this->roleValue($user)) {
            'supervisor' => [
                'title' => 'Supervisor dashboard',
                'description' => 'Workforce attendance and today’s production activity.',
            ],
            'operator' => [
                'title' => 'Operator dashboard',
                'description' => 'Your attendance, production and efficiency.',
            ],
            'bandsaw' => [
                'title' => 'Bandsaw dashboard',
                'description' => 'Your attendance and cutting activity.',
            ],
            'deburring_packaging' => [
                'title' => 'Deburring & packaging dashboard',
                'description' => 'Your attendance overview.',
            ],
            default => [
                'title' => 'Dashboard',
                'description' => 'Your attendance overview.',
            ],
        };
    }

    /**
     * Each key is an independent Inertia deferred prop.
     */
    public function deferredPropsForUser(User $user): array
    {
        $today = today()->toDateString();
        $role = $this->roleValue($user);

        $attendanceProps = [
            'todayShift' => fn (): array => $this->todayShift($user, $today),
            'monthlyAttendance' => fn (): array => $this->monthlyAttendance(
                (int) $user->id,
            ),
        ];

        return match ($role) {
            'supervisor' => [
                ...$attendanceProps,

                'stats' => fn (): array => $this->supervisorStats(),

                'attendanceOverview' => fn (): array => $this->attendanceOverview(),

                'recentOperatorLogs' => fn (): array => [
                    'title' => "Today's operator production",
                    'emptyMessage' => 'No operator work logs have been recorded today.',
                    'items' => $this->recentOperatorLogs($today),
                ],

                'recentBandsawLogs' => fn (): array => [
                    'title' => "Today's bandsaw activity",
                    'emptyMessage' => 'No bandsaw work logs have been recorded today.',
                    'items' => $this->recentBandsawLogs($today),
                ],
            ],

            'operator' => [
                ...$attendanceProps,

                'stats' => fn (): array => $this->operatorStats($user, $today),

                'recentOperatorLogs' => fn (): array => [
                    'title' => "Today's production",
                    'emptyMessage' => 'You have no operator work logs for today.',
                    'items' => $this->recentOperatorLogs(
                        $today,
                        (int) $user->id,
                    ),
                ],
            ],

            'bandsaw' => [
                ...$attendanceProps,

                'stats' => fn (): array => $this->bandsawStats($user, $today),

                'recentBandsawLogs' => fn (): array => [
                    'title' => "Today's cutting activity",
                    'emptyMessage' => 'You have no bandsaw work logs for today.',
                    'items' => $this->recentBandsawLogs(
                        $today,
                        (int) $user->id,
                    ),
                ],
            ],

            'deburring_packaging' => $attendanceProps,

            default => $attendanceProps,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Supervisor stats
    |--------------------------------------------------------------------------
    */

    private function supervisorStats(): array
    {
        $today = today()->toDateString();
        $tomorrow = $this->nextDate($today);

        $activeEmployees = User::query()
            ->where('status', Status::ACTIVE->value)
            ->count();

        $totalEmployees = User::query()->count();

        $attendanceToday = Attendance::query()
            ->where('attendance_date', '>=', $today)
            ->where('attendance_date', '<', $tomorrow);

        $operatorToday = OperatorWorkLog::query()
            ->where('work_date', '>=', $today)
            ->where('work_date', '<', $tomorrow);

        $bandsawToday = BandsawWorkLog::query()
            ->where('work_date', '>=', $today)
            ->where('work_date', '<', $tomorrow);

        $good = (int) (clone $operatorToday)->sum('quantity');
        $rejected = (int) (clone $operatorToday)->sum('rejection_quantity');

        return [
            $this->stat(
                'Employees',
                $totalEmployees,
                "{$activeEmployees} active",
            ),
            $this->stat(
                'Present today',
                (clone $attendanceToday)->distinct()->count('user_id'),
                'Employees with an attendance record',
            ),
            $this->stat(
                'Open shifts',
                (clone $attendanceToday)->whereNull('end_at')->count(),
                'Attendance records not yet clocked out',
            ),
            $this->stat(
                'Total production today',
                $good + $rejected,
                'Good plus rejected operator quantity',
            ),
            $this->stat(
                'Good pieces today',
                $good,
                'Recorded good quantity',
            ),
            $this->stat(
                'Rejected pieces today',
                $rejected,
                'Recorded rejected quantity',
            ),
            $this->stat(
                'Bandsaw output today',
                (int) (clone $bandsawToday)->sum('quantity'),
                'Cutting quantity, separate from operator output',
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Operator stats
    |--------------------------------------------------------------------------
    */

    private function operatorStats(User $user, string $today): array
    {
        $logs = OperatorWorkLog::query()
            ->where('user_id', $user->id)
            ->where('work_date', '>=', $today)
            ->where('work_date', '<', $this->nextDate($today));

        $good = (int) (clone $logs)->sum('quantity');
        $rejected = (int) (clone $logs)->sum('rejection_quantity');

        $efficiency = $this->efficiencyService->calculateForDay(
            userId: (int) $user->id,
            date: Carbon::parse($today),
        );

        return [
            $this->stat(
                "Today's Efficiency",
                $efficiency ?? '—',
                'Production time vs. effective working time',
            ),
            $this->stat(
                'Good pieces today',
                $good,
                'Recorded good quantity',
            ),
            $this->stat(
                'Rejected pieces today',
                $rejected,
                'Recorded rejected quantity',
            ),
            $this->stat(
                'Total produced today',
                $good + $rejected,
                'Good plus rejected quantity',
            ),
            $this->stat(
                'Production time',
                $this->formatDuration(
                    (int) (clone $logs)->sum('production_seconds'),
                ),
                'Recorded production time',
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Bandsaw stats
    |--------------------------------------------------------------------------
    */

    private function bandsawStats(User $user, string $today): array
    {
        $logs = BandsawWorkLog::query()
            ->where('user_id', $user->id)
            ->where('work_date', '>=', $today)
            ->where('work_date', '<', $this->nextDate($today));

        return [
            $this->stat(
                'Cutting quantity today',
                (int) (clone $logs)->sum('quantity'),
                'Quantity logged today',
            ),
            $this->stat(
                'Cutting entries',
                (int) (clone $logs)->count(),
                'Work log entries today',
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance overview (Supervisor)
    |--------------------------------------------------------------------------
    */

    private function attendanceOverview(): array
    {
        $today = today()->toDateString();

        $activeEmployees = User::query()
            ->where('status', Status::ACTIVE->value)
            ->count();

        $records = Attendance::query()
            ->where('attendance_date', '>=', $today)
            ->where('attendance_date', '<', $this->nextDate($today));

        $present = (clone $records)->distinct()->count('user_id');
        $openShifts = (clone $records)->whereNull('end_at')->count();

        return [
            'activeEmployees' => $activeEmployees,
            'present' => $present,
            'notRecorded' => max(0, $activeEmployees - $present),
            'openShifts' => $openShifts,
            'attendanceRate' => $this->percentage($present, $activeEmployees),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Monthly attendance
    |--------------------------------------------------------------------------
    */

    private function monthlyAttendance(int $userId): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = today()->toDateString();

        $row = Attendance::query()
            ->where('user_id', $userId)
            ->where('attendance_date', '>=', $start)
            ->where('attendance_date', '<', $this->nextDate($end))
            ->selectRaw('COUNT(DISTINCT attendance_date) AS present_days')
            ->selectRaw(
                'SUM(CASE WHEN end_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_shifts',
            )
            ->selectRaw(
                'SUM(CASE WHEN end_at IS NULL THEN 1 ELSE 0 END) AS open_shifts',
            )
            ->selectRaw(
                'COALESCE(SUM(effective_working_seconds), 0) AS effective_seconds',
            )
            ->selectRaw(
                'COALESCE(SUM(total_working_seconds), 0) AS total_seconds',
            )
            ->selectRaw(
                'COALESCE(SUM(break_seconds), 0) AS break_seconds',
            )
            ->first();

        return [
            'month' => now()->format('F Y'),
            'presentDays' => (int) $row->present_days,
            'completedShifts' => (int) $row->completed_shifts,
            'openShifts' => (int) $row->open_shifts,
            'effectiveSeconds' => (int) $row->effective_seconds,
            'totalSeconds' => (int) $row->total_seconds,
            'breakSeconds' => (int) $row->break_seconds,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Today's attendance and shift
    |--------------------------------------------------------------------------
    */

    private function todayShift(User $user, string $today): array
    {
        $attendance = Attendance::query()
            ->with('breaks')
            ->where('user_id', $user->id)
            ->where('attendance_date', '>=', $today)
            ->where('attendance_date', '<', $this->nextDate($today))
            ->first();

        $shift = $this->enumValue($user->shift);

        $shiftLabel = match ($shift) {
            'day' => 'Day shift',
            'night' => 'Night shift',
            '8_hr' => '8-hour shift',
            '8_hr_total' => '8-hour total shift',
            default => 'Shift not configured',
        };

        if (! $attendance) {
            return [
                'status' => 'not_recorded',
                'statusLabel' => 'Not recorded',
                'shiftLabel' => $shiftLabel,
                'checkIn' => null,
                'checkOut' => null,
                'totalSeconds' => 0,
                'breakSeconds' => 0,
                'effectiveSeconds' => 0,
                'activeBreak' => false,
                'breaks' => [],
            ];
        }

        $now = now();
        $startAt = $attendance->start_at;
        $endAt = $attendance->end_at;

        $totalSeconds = $startAt
            ? $this->secondsBetween($startAt, $endAt ?? $now)
            : 0;

        $breakSeconds = 0;
        $activeBreak = false;
        $breaks = [];

        foreach ($attendance->breaks as $break) {
            $breakStart = $break->start_at;
            $breakEnd = $break->end_at;
            $isOngoing = $breakEnd === null;

            $duration = $isOngoing
                ? $this->secondsBetween($breakStart, $endAt ?? $now)
                : (int) $break->duration_seconds;

            $breakSeconds += $duration;
            $activeBreak = $activeBreak || $isOngoing;

            $breaks[] = [
                'startAt' => $breakStart?->format('g:i A'),
                'endAt' => $breakEnd?->format('g:i A'),
                'durationSeconds' => $duration,
                'isOngoing' => $isOngoing,
            ];
        }

        // Preserve the existing minimum one-hour break rule.
        $breakSeconds = max(3600, $breakSeconds);

        $status = $endAt !== null
            ? 'completed'
            : ($activeBreak ? 'on_break' : 'on_shift');

        return [
            'status' => $status,
            'statusLabel' => match ($status) {
                'completed' => 'Completed',
                'on_break' => 'On break',
                default => 'On shift',
            },
            'shiftLabel' => $shiftLabel,
            'checkIn' => $startAt?->format('g:i A'),
            'checkOut' => $endAt?->format('g:i A'),
            'totalSeconds' => $totalSeconds,
            'breakSeconds' => $breakSeconds,
            'effectiveSeconds' => max(0, $totalSeconds - $breakSeconds),
            'activeBreak' => $activeBreak,
            'breaks' => $breaks,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Today's operator logs
    |--------------------------------------------------------------------------
    */

    private function recentOperatorLogs(
        string $date,
        ?int $userId = null,
    ): array {
        return OperatorWorkLog::query()
            ->join('users', 'users.id', '=', 'operator_work_logs.user_id')
            ->join('machines', 'machines.id', '=', 'operator_work_logs.machine_id')
            ->join('parts', 'parts.id', '=', 'operator_work_logs.part_id')
            ->join('setups', 'setups.id', '=', 'operator_work_logs.setup_id')
            ->where('operator_work_logs.work_date', '>=', $date)
            ->where('operator_work_logs.work_date', '<', $this->nextDate($date))
            ->when(
                $userId !== null,
                fn (Builder $query) => $query->where(
                    'operator_work_logs.user_id',
                    $userId,
                ),
            )
            ->orderByDesc('operator_work_logs.id')
            ->limit(self::RECENT_LIMIT)
            ->get([
                'users.name as employee_name',
                'users.employee_code',
                'machines.name as machine_name',
                'parts.name as part_name',
                'setups.name as setup_name',
                'operator_work_logs.quantity',
                'operator_work_logs.rejection_quantity',
                'operator_work_logs.production_seconds',
            ])
            ->map(fn ($row) => [
                'title' => "{$row->part_name} · {$row->machine_name}",
                'subtitle' => $userId === null
                    ? "{$row->employee_name} ({$row->employee_code}) · {$row->setup_name}"
                    : "Setup: {$row->setup_name}",
                'meta' => 'Good '.$row->quantity
                    .' · Rejected '.$row->rejection_quantity
                    .' · '.$this->formatDuration(
                        (int) $row->production_seconds,
                    ),
            ])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Today's bandsaw logs
    |--------------------------------------------------------------------------
    */

    private function recentBandsawLogs(
        string $date,
        ?int $userId = null,
    ): array {
        return BandsawWorkLog::query()
            ->join('users', 'users.id', '=', 'bandsaw_work_logs.user_id')
            ->join('parts', 'parts.id', '=', 'bandsaw_work_logs.part_id')
            ->join(
                'section_sizes',
                'section_sizes.id',
                '=',
                'bandsaw_work_logs.section_size_id',
            )
            ->join('sections', 'sections.id', '=', 'section_sizes.section_id')
            ->where('bandsaw_work_logs.work_date', '>=', $date)
            ->where('bandsaw_work_logs.work_date', '<', $this->nextDate($date))
            ->when(
                $userId !== null,
                fn (Builder $query) => $query->where(
                    'bandsaw_work_logs.user_id',
                    $userId,
                ),
            )
            ->orderByDesc('bandsaw_work_logs.id')
            ->limit(self::RECENT_LIMIT)
            ->get([
                'users.name as employee_name',
                'users.employee_code',
                'parts.name as part_name',
                'sections.name as section_name',
                'section_sizes.size as section_size',
                'bandsaw_work_logs.quantity',
            ])
            ->map(fn ($row) => [
                'title' => "{$row->part_name} · {$row->section_name} {$row->section_size}",
                'subtitle' => $userId === null
                    ? "{$row->employee_name} ({$row->employee_code})"
                    : 'Cutting activity',
                'meta' => 'Quantity '.$row->quantity,
            ])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function nextDate(string $date): string
    {
        return Carbon::parse($date)->addDay()->toDateString();
    }

    private function stat(
        string $label,
        int|float|string $value,
        string $description,
    ): array {
        return compact('label', 'value', 'description');
    }

    private function percentage(
        int|float $numerator,
        int|float $denominator,
    ): float {
        return $denominator > 0
            ? round(($numerator / $denominator) * 100, 2)
            : 0.0;
    }

    private function secondsBetween(
        ?CarbonInterface $start,
        ?CarbonInterface $end,
    ): int {
        if (! $start || ! $end) {
            return 0;
        }

        return max(0, $end->getTimestamp() - $start->getTimestamp());
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    }

    private function roleValue(User $user): string
    {
        return $this->enumValue($user->role);
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum
            ? (string) $value->value
            : (string) ($value ?? '');
    }
}
