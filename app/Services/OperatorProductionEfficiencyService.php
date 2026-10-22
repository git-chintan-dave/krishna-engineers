<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\OperatorWorkLog;
use Carbon\CarbonInterface;

class OperatorProductionEfficiencyService
{
    public function calculateForDay(
        int $userId,
        CarbonInterface $date,
    ): ?float {
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
            $productionSeconds,
            $attendance->effective_working_seconds,
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

    public function calculate(
        int $productionSeconds,
        int $effectiveWorkingSeconds,
    ): ?float {
        if ($effectiveWorkingSeconds <= 0) {
            return null;
        }

        return round(
            ($productionSeconds / $effectiveWorkingSeconds) * 100,
            2,
        );
    }
}
