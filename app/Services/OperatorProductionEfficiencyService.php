<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\OperatorWorkLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

class OperatorProductionEfficiencyService
{
    public function calculateForDay(
        int $userId,
        CarbonInterface $date,
    ): ?string {
        $attendance = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', $date)
            ->first();

        if (! $attendance) {
            return null;
        }

        $productionSeconds = $this->getProductionSecondsForDay(
            $userId,
            $date,
        );

        return $this->calculate(
            productionSeconds: $productionSeconds,
            effectiveWorkingSeconds: (int) $attendance->effective_working_seconds,
        );
    }

    public function getProductionSecondsForDay(
        int $userId,
        CarbonInterface $date,
    ): int {
        return (int) OperatorWorkLog::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->sum('production_seconds');
    }

    /**
     * Calculate production efficiency for an employee over a date range.
     */
    public function calculateForPeriod(
        int $userId,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): ?string {
        $productionSeconds = $this->getProductionSecondsForPeriod(
            $userId,
            $startDate,
            $endDate,
        );

        $effectiveWorkingSeconds = $this->getEffectiveWorkingSecondsForPeriod(
            $userId,
            $startDate,
            $endDate,
        );

        return $this->calculate(
            productionSeconds: $productionSeconds,
            effectiveWorkingSeconds: $effectiveWorkingSeconds,
        );
    }

    /**
     * Get total production seconds over a date range.
     */
    public function getProductionSecondsForPeriod(
        int $userId,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): int {
        return (int) OperatorWorkLog::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', '>=', $startDate->toDateString())
            ->whereDate('work_date', '<=', $endDate->toDateString())
            ->sum('production_seconds');
    }

    /**
     * Get total effective working seconds over a date range.
     */
    public function getEffectiveWorkingSecondsForPeriod(
        int $userId,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): int {
        return (int) Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', '>=', $startDate->toDateString())
            ->whereDate('attendance_date', '<=', $endDate->toDateString())
            ->sum('effective_working_seconds');
    }

    /**
     * Calculate efficiency from production and effective working seconds.
     */
    public function calculate(
        int $productionSeconds,
        int $effectiveWorkingSeconds,
    ): ?string {
        if ($effectiveWorkingSeconds <= 0) {
            return null;
        }

        return Number::percentage(
            number: ($productionSeconds / $effectiveWorkingSeconds) * 100,
            precision: 2,
            locale: 'en_IN'
        );
    }
}
