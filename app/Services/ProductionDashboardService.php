<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BandsawWorkLog;
use App\Models\OperatorWorkLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

class ProductionDashboardService
{
    public function __construct(
        private readonly OperatorProductionEfficiencyService $efficiencyService,
    ) {}

    public function getDashboard(): array
    {
        $today = CarbonImmutable::today();

        $currentStart = $today->startOfMonth();
        $previousStart = $today->subMonth()->startOfMonth();
        $previousEnd = $today->subMonth()->endOfMonth();

        return [
            'currentPeriod' => $this->getPeriod(
                startDate: $currentStart,
                endDate: $today,
                label: 'Current Month',
            ),

            'previousPeriod' => $this->getPeriod(
                startDate: $previousStart,
                endDate: $previousEnd,
                label: 'Previous Month',
            ),
        ];
    }

    private function getPeriod(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        string $label,
    ): array {
        $operator = $this->getOperatorProduction($startDate, $endDate);
        $bandsaw = $this->getBandsawProduction($startDate, $endDate);

        return [
            'label' => $label,
            'operator' => $operator,
            'bandsaw' => $bandsaw,
            'daily' => $this->getDailyProduction($startDate, $endDate),
        ];
    }

    private function getOperatorProduction(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): array {
        $query = OperatorWorkLog::query()
            ->whereDate('work_date', '>=', $startDate->toDateString())
            ->whereDate('work_date', '<=', $endDate->toDateString());

        $summary = (clone $query)
            ->selectRaw('
                COALESCE(SUM(quantity), 0) as total_quantity,
                COALESCE(SUM(rejection_quantity), 0) as rejection_quantity,
                COALESCE(SUM(production_seconds), 0) as production_seconds,
                COUNT(*) as work_entries,
                COUNT(DISTINCT machine_id) as machine_count,
                COUNT(DISTINCT part_id) as part_count
            ')
            ->first();

        /*
         * Match the attendance denominator to the same period.
         * Only Operator-role employees are included.
         */
        $effectiveWorkingSeconds = Attendance::query()
            ->whereIn('user_id', User::query()->where('role', 'operator')->select('id'))
            ->whereDate('attendance_date', '>=', $startDate->toDateString())
            ->whereDate('attendance_date', '<=', $endDate->toDateString())
            ->sum('effective_working_seconds');

        $effectiveWorkingSeconds = $effectiveWorkingSeconds !== null
            ? (int) $effectiveWorkingSeconds
            : null;

        $totalQuantity = (int) $summary->total_quantity;
        $rejectionQuantity = (int) $summary->rejection_quantity;
        $goodQuantity = max(0, $totalQuantity - $rejectionQuantity);
        $productionSeconds = (int) $summary->production_seconds;

        $productionEfficiency = $effectiveWorkingSeconds !== null
            ? $this->efficiencyService->calculate(
                productionSeconds: $productionSeconds,
                effectiveWorkingSeconds: $effectiveWorkingSeconds,
            )
            : null;

        $machines = (clone $query)
            ->join('machines', 'machines.id', '=', 'operator_work_logs.machine_id')
            ->selectRaw('
                machines.id,
                machines.name,
                COALESCE(SUM(operator_work_logs.quantity), 0) as quantity,
                COALESCE(SUM(operator_work_logs.rejection_quantity), 0) as rejected
            ')
            ->groupBy('machines.id', 'machines.name')
            ->orderByDesc('quantity')
            ->get()
            ->map(fn ($machine) => [
                'name' => $machine->name,
                'quantity' => (int) $machine->quantity,
                'quantityFormatted' => $this->formatNumber((int) $machine->quantity),
                'rejected' => (int) $machine->rejected,
                'rejectedFormatted' => $this->formatNumber((int) $machine->rejected),
            ])
            ->values();

        $rejectionRate = $totalQuantity > 0
            ? ($rejectionQuantity / $totalQuantity) * 100
            : 0;

        $goodRate = $totalQuantity > 0
            ? ($goodQuantity / $totalQuantity) * 100
            : 0;

        return [
            'totalQuantity' => $totalQuantity,
            'totalQuantityFormatted' => $this->formatNumber($totalQuantity),

            'goodQuantity' => $goodQuantity,
            'goodQuantityFormatted' => $this->formatNumber($goodQuantity),

            'rejectionQuantity' => $rejectionQuantity,
            'rejectionQuantityFormatted' => $this->formatNumber($rejectionQuantity),

            'productionSeconds' => $productionSeconds,
            'productionTimeFormatted' => $this->formatDuration($productionSeconds),

            'effectiveWorkingSeconds' => $effectiveWorkingSeconds,
            'effectiveWorkingTimeFormatted' => $effectiveWorkingSeconds !== null
                ? $this->formatDuration($effectiveWorkingSeconds)
                : null,

            'productionEfficiency' => $productionEfficiency,

            'rejectionRate' => round($rejectionRate, 2),
            'rejectionRateFormatted' => Number::percentage($rejectionRate, 2),

            'goodRate' => round($goodRate, 2),
            'goodRateFormatted' => Number::percentage($goodRate, 2),

            'workEntries' => (int) $summary->work_entries,
            'machineCount' => (int) $summary->machine_count,
            'partCount' => (int) $summary->part_count,

            'machines' => $machines,
        ];
    }

    private function getBandsawProduction(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): array {
        $query = BandsawWorkLog::query()
            ->whereDate('work_date', '>=', $startDate->toDateString())
            ->whereDate('work_date', '<=', $endDate->toDateString());

        $summary = (clone $query)
            ->selectRaw('
                COALESCE(SUM(quantity), 0) as total_quantity,
                COUNT(*) as work_entries,
                COUNT(DISTINCT part_id) as part_count,
                COUNT(DISTINCT section_size_id) as section_size_count
            ')
            ->first();


        $sectionSizes = (clone $query)
            ->join(
                'section_sizes',
                'section_sizes.id',
                '=',
                'bandsaw_work_logs.section_size_id',
            )
            ->join(
                'sections',
                'sections.id',
                '=',
                'section_sizes.section_id',
            )
            ->selectRaw('
                section_sizes.id,
                sections.name as section_name,
                section_sizes.size,
                COALESCE(SUM(bandsaw_work_logs.quantity), 0) as quantity
            ')
            ->groupBy(
                'section_sizes.id',
                'sections.name',
                'section_sizes.size',
            )
            ->orderByDesc('quantity')
            ->get()
            ->map(fn ($sectionSize) => [
                'name' => $sectionSize->section_name . ' - ' . $sectionSize->size,
                'quantity' => (int) $sectionSize->quantity,
                'quantityFormatted' => $this->formatNumber((int) $sectionSize->quantity),
            ])
            ->values();

        $totalQuantity = (int) $summary->total_quantity;

        return [
            'totalQuantity' => $totalQuantity,
            'totalQuantityFormatted' => $this->formatNumber($totalQuantity),

            'workEntries' => (int) $summary->work_entries,
            'partCount' => (int) $summary->part_count,
            'sectionSizeCount' => (int) $summary->section_size_count,

            'sectionSizes' => $sectionSizes,
        ];
    }

    private function getDailyProduction(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): array {
        $operatorDaily = OperatorWorkLog::query()
            ->whereDate('work_date', '>=', $startDate->toDateString())
            ->whereDate('work_date', '<=', $endDate->toDateString())
            ->selectRaw('
                DATE(work_date) as work_date,
                COALESCE(SUM(quantity), 0) as quantity,
                COALESCE(SUM(rejection_quantity), 0) as rejected
            ')
            ->groupByRaw('DATE(work_date)')
            ->get()
            ->keyBy('work_date');

        $bandsawDaily = BandsawWorkLog::query()
            ->whereDate('work_date', '>=', $startDate->toDateString())
            ->whereDate('work_date', '<=', $endDate->toDateString())
            ->selectRaw('
                DATE(work_date) as work_date,
                COALESCE(SUM(quantity), 0) as quantity
            ')
            ->groupByRaw('DATE(work_date)')
            ->get()
            ->keyBy('work_date');

        $dates = $operatorDaily->keys()
            ->merge($bandsawDaily->keys())
            ->unique()
            ->sort()
            ->values();

        return $dates->map(function (string $date) use ($operatorDaily, $bandsawDaily) {
            $operator = $operatorDaily->get($date);
            $bandsaw = $bandsawDaily->get($date);

            $operatorQuantity = (int) ($operator?->quantity ?? 0);
            $operatorRejected = (int) ($operator?->rejected ?? 0);
            $bandsawQuantity = (int) ($bandsaw?->quantity ?? 0);

            return [
                'date' => CarbonImmutable::parse($date)->format('d M'),
                'workDate' => $date,

                'operatorQuantity' => $operatorQuantity,
                'operatorQuantityFormatted' => $this->formatNumber($operatorQuantity),

                'operatorRejected' => $operatorRejected,
                'operatorRejectedFormatted' => $this->formatNumber($operatorRejected),

                'bandsawQuantity' => $bandsawQuantity,
                'bandsawQuantityFormatted' => $this->formatNumber($bandsawQuantity),
            ];
        })->all();
    }

    private function formatNumber(int $value): string
    {
        return Number::format($value, locale: 'en_IN');
    }

    private function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return "{$hours}h {$minutes}m";
    }
}
