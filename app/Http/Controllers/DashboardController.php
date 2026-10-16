<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\OperatorWorkLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        $isSuperAdmin = $role === UserRole::SUPER_ADMIN->value;
        $isSupervisor = $role === UserRole::SUPERVISOR->value;
        $isOperator = $role === UserRole::OPERATOR->value;
        $isBandsaw = $role === UserRole::BANDSAW->value;
        $canViewOperations = $isSuperAdmin || $isSupervisor;

        $today = Carbon::today();
        $startDate = $today->copy()->subDays(29);

        $workLogs = OperatorWorkLog::query()
            ->when(
                $isOperator,
                fn (Builder $query): Builder => $query->where('user_id', $user->id),
            )
            ->when(
                ! $canViewOperations && ! $isOperator,
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            );

        $todaySummary = (clone $workLogs)
            ->whereDate('work_date', $today)
            ->selectRaw('COUNT(*) AS log_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(rejection_quantity), 0) AS rejected')
            ->selectRaw('COALESCE(SUM(production_seconds), 0) AS production_seconds')
            ->selectRaw('COUNT(DISTINCT machine_id) AS machines_used')
            ->first();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $stats = [];

        if ($canViewOperations) {
            $activeOperatorIds = User::query()
                ->where('role', UserRole::OPERATOR->value)
                ->where('status', Status::ACTIVE->value)
                ->select('id');

            $presentOperators = Attendance::query()
                ->whereDate('attendance_date', $today)
                ->whereIn('user_id', $activeOperatorIds)
                ->distinct()
                ->count('user_id');

            $activeOperators = User::query()
                ->where('role', UserRole::OPERATOR->value)
                ->where('status', Status::ACTIVE->value)
                ->count();

            $stats = [
                $this->stat(
                    'Operators Present',
                    number_format($presentOperators),
                    'Today',
                ),
                $this->stat(
                    'Machines Used',
                    number_format((int) $todaySummary->machines_used),
                    'Today',
                ),
                $this->stat(
                    'Production Quantity',
                    number_format((int) $todaySummary->quantity),
                    'Today',
                ),
                $this->stat(
                    'Rejected Quantity',
                    number_format((int) $todaySummary->rejected),
                    'Today',
                ),
                $this->stat(
                    'Production Time',
                    $this->formatDuration((int) $todaySummary->production_seconds),
                    'Today',
                ),
                $this->stat(
                    'Operators Absent',
                    number_format(max(0, $activeOperators - $presentOperators)),
                    'Today',
                ),
            ];
        } elseif ($isOperator) {
            $stats = [
                $this->stat(
                    'Production Quantity',
                    number_format((int) $todaySummary->quantity),
                    'My production today',
                ),
                $this->stat(
                    'Rejected Quantity',
                    number_format((int) $todaySummary->rejected),
                    'My production today',
                ),
                $this->stat(
                    'Production Time',
                    $this->formatDuration((int) $todaySummary->production_seconds),
                    'My production today',
                ),
                $this->stat(
                    'Work Logs',
                    number_format((int) $todaySummary->log_count),
                    'Recorded today',
                ),
                $this->stat(
                    'Effective Hours',
                    $attendance && $attendance->effective_working_seconds > 0
                        ? $this->formatDuration((int) $attendance->effective_working_seconds)
                        : '—',
                    'Today',
                ),
            ];
        } else {
            $stats = [
                $this->stat(
                    'Attendance',
                    $attendance ? 'Present' : 'Not recorded',
                    'Today',
                ),
                $this->stat(
                    'Effective Hours',
                    $attendance && $attendance->effective_working_seconds > 0
                        ? $this->formatDuration((int) $attendance->effective_working_seconds)
                        : '—',
                    'Today',
                ),
            ];
        }

        $trendRows = (clone $workLogs)
            ->whereBetween('work_date', [$startDate->toDateString(), $today->toDateString()])
            ->selectRaw('work_date')
            ->selectRaw('COALESCE(SUM(quantity), 0) AS production_quantity')
            ->selectRaw('COALESCE(SUM(rejection_quantity), 0) AS rejection_quantity')
            ->groupBy('work_date')
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($row): string => Carbon::parse($row->work_date)->toDateString());

        $productionTrend = collect(range(0, 29))
            ->map(function (int $offset) use ($startDate, $trendRows): array {
                $date = $startDate->copy()->addDays($offset);
                $row = $trendRows->get($date->toDateString());

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('M j'),
                    'productionQuantity' => (int) ($row->production_quantity ?? 0),
                    'rejectionQuantity' => (int) ($row->rejection_quantity ?? 0),
                ];
            })
            ->values();

        $machinePerformance = collect();
        $operatorPerformance = collect();
        $attendanceSummary = collect();

        if ($canViewOperations) {
            $machinePerformance = (clone $workLogs)
                ->whereDate('work_date', $today)
                ->selectRaw('machine_id')
                ->selectRaw('COUNT(DISTINCT part_id) AS parts')
                ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity')
                ->selectRaw('COALESCE(SUM(production_seconds), 0) AS production_seconds')
                ->with('machine:id,name,reference_name')
                ->groupBy('machine_id')
                ->orderByDesc('quantity')
                ->limit(8)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->machine_id,
                    'referenceName' => $row->machine?->reference_name ?? '—',
                    'name' => $row->machine?->name ?? 'Unknown machine',
                    'parts' => (int) $row->parts,
                    'quantity' => (int) $row->quantity,
                    'productionTime' => $this->formatDuration((int) $row->production_seconds),
                ])
                ->values();

            $operatorPerformance = (clone $workLogs)
                ->whereDate('work_date', $today)
                ->selectRaw('user_id')
                ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity')
                ->selectRaw('COALESCE(SUM(rejection_quantity), 0) AS rejection_quantity')
                ->selectRaw('COALESCE(SUM(production_seconds), 0) AS production_seconds')
                ->selectSub(
                    Attendance::query()
                        ->select('effective_working_seconds')
                        ->whereColumn('user_id', 'operator_work_logs.user_id')
                        ->whereDate('attendance_date', $today)
                        ->limit(1),
                    'effective_working_seconds',
                )
                ->with('user:id,name,employee_code')
                ->groupBy('user_id')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->user_id,
                    'name' => $row->user?->name ?? 'Unknown operator',
                    'quantity' => (int) $row->quantity,
                    'rejectionQuantity' => (int) $row->rejection_quantity,
                    'productionTime' => $this->formatDuration((int) $row->production_seconds),
                    'effectiveWorkingSeconds' => $row->effective_working_seconds !== null
                        ? (int) $row->effective_working_seconds
                        : null,

                    'efficiency' => $row->effective_working_seconds !== null
                    && (int) $row->effective_working_seconds > 0
                        ? round(
                            ((int) $row->production_seconds / (int) $row->effective_working_seconds) * 100,
                            1,
                        )
                        : null,
                ])
                ->values();

            $activeOperatorIds = User::query()
                ->where('role', UserRole::OPERATOR->value)
                ->where('status', Status::ACTIVE->value)
                ->select('id');

            $present = Attendance::query()
                ->whereDate('attendance_date', $today)
                ->whereIn('user_id', $activeOperatorIds);

            $attendanceSummary = collect([
                ['label' => 'Present', 'value' => (clone $present)->distinct()->count('user_id')],
                ['label' => 'Working', 'value' => (clone $present)->whereNull('end_at')->distinct()->count('user_id')],
                ['label' => 'Logged Out', 'value' => (clone $present)->whereNotNull('end_at')->distinct()->count('user_id')],
                [
                    'label' => 'Absent',
                    'value' => max(
                        0,
                        User::query()
                            ->where('role', UserRole::OPERATOR->value)
                            ->where('status', Status::ACTIVE->value)
                            ->count() - (clone $present)->distinct()->count('user_id'),
                    ),
                ],
            ]);
        } elseif ($isOperator) {
            $operatorRow = (clone $workLogs)
                ->whereDate('work_date', $today)
                ->selectRaw('user_id')
                ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity')
                ->selectRaw('COALESCE(SUM(rejection_quantity), 0) AS rejection_quantity')
                ->selectRaw('COALESCE(SUM(production_seconds), 0) AS production_seconds')
                ->groupBy('user_id')
                ->first();

            if ($operatorRow) {
                $operatorPerformance = collect([[
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'quantity' => (int) $operatorRow->quantity,
                    'rejectionQuantity' => (int) $operatorRow->rejection_quantity,
                    'productionTime' => $this->formatDuration((int) $operatorRow->production_seconds),
                ]]);
            }
        }

        $recentActivity = (clone $workLogs)
            ->with(['user:id,name', 'machine:id,name', 'part:id,name'])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (OperatorWorkLog $log): array => [
                'id' => $log->id,
                'description' => sprintf(
                    '%s recorded %s pieces of %s%s.',
                    $log->user?->name ?? 'An operator',
                    number_format((int) $log->quantity),
                    $log->part?->name ?? 'a part',
                    $log->machine?->name ? ' on '.$log->machine->name : '',
                ),
                'time' => $log->created_at?->format('M j, g:i A') ?? '',
                'type' => 'operator_work_log',
            ])
            ->values();

        $quickActions = [];

        if ($canViewOperations || $isOperator) {
            $quickActions[] = [
                'label' => 'Operator Work Log',
                'description' => $isOperator ? 'Record your production' : 'Record operator production',
                'href' => '/work-logs/operator/create',
                'icon' => 'ClipboardList',
            ];
        }

        if ($canViewOperations || $isBandsaw) {
            $quickActions[] = [
                'label' => 'Bandsaw Work Log',
                'description' => $isBandsaw ? 'Record your cutting work' : 'Record bandsaw production',
                'href' => '/work-logs/bandsaw/create',
                'icon' => 'Scissors',
            ];
        }

        return Inertia::render('Dashboard', [
            'role' => $role,
            'stats' => $stats,
            'quickActions' => $quickActions,
            'productionTrend' => $productionTrend,
            'machinePerformance' => $machinePerformance,
            'attendanceSummary' => $attendanceSummary,
            'operatorPerformance' => $operatorPerformance,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * @return array{label: string, value: string, description: string}
     */
    private function stat(string $label, string $value, string $description): array
    {
        return compact('label', 'value', 'description');
    }

    private function formatDuration(int $seconds): string
    {
        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    }
}
