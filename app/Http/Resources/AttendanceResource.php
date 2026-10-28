<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\OperatorProductionEfficiencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attendanceService = app(AttendanceService::class);

        $productionEfficiencyService = app(
            OperatorProductionEfficiencyService::class,
        );

        $productionSeconds = (int) $productionEfficiencyService
            ->getProductionSecondsForDay(
                $this->user_id,
                $this->attendance_date,
            );

        $effectiveWorkingSeconds = (int) $this->effective_working_seconds;

        $productionEfficiency = $productionEfficiencyService->calculate(
            $productionSeconds,
            $effectiveWorkingSeconds,
        );

        $startAt = $this->start_at;
        $endAt = $this->end_at;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            /*
            |--------------------------------------------------------------------------
            | Employee
            |--------------------------------------------------------------------------
            */

            'user_id' => $this->user_id,

            'user' => $this->whenLoaded(
                'user',
                fn (): array => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'employee_code' => $this->user->employee_code,
                ],
            ),

            /*
            |--------------------------------------------------------------------------
            | Attendance
            |--------------------------------------------------------------------------
            */

            'attendance_date' => $this->attendance_date?->toDateString(),

            'attendance_date_formatted' => $this->attendance_date?->format(
                'd M Y',
            ),

            'start_at' => $startAt?->format('Y-m-d H:i:s'),

            'end_at' => $endAt?->format('Y-m-d H:i:s'),

            'start_at_formatted' => $startAt?->format('h:i A'),

            'end_at_formatted' => $endAt?->format('h:i A'),

            'is_open' => $this->isOpen(),

            'needs_attention' => $this->needsAttention(),

            /*
            |--------------------------------------------------------------------------
            | Working Time
            |--------------------------------------------------------------------------
            */

            'total_working_seconds' => $this->total_working_seconds,

            'total_working_seconds_formatted' => self::formatDuration(
                $this->total_working_seconds,
            ),

            'break_seconds' => $this->break_seconds,

            'break_seconds_formatted' => self::formatDuration(
                $this->break_seconds,
            ),

            'effective_working_seconds' => $this->effective_working_seconds,

            'effective_working_seconds_formatted' => self::formatDuration(
                $this->effective_working_seconds,
            ),

            'undertime_seconds' => $this->undertime_seconds,

            'undertime_seconds_formatted' => $this->undertime_seconds !== null
                ? self::formatDuration($this->undertime_seconds)
                : null,

            /*
            |--------------------------------------------------------------------------
            | Production
            |--------------------------------------------------------------------------
            */

            'production_seconds' => $productionSeconds,

            'production_seconds_formatted' => self::formatDuration(
                $productionSeconds,
            ),

            'production_efficiency' => $productionEfficiency,

            /*
            |--------------------------------------------------------------------------
            | Breaks
            |--------------------------------------------------------------------------
            */

            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks'),
            ),

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at?->format('d M Y'),

            'updated_at' => $this->updated_at?->format('d M Y'),

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_locked' => $attendanceService->isLocked($this->resource),
        ];
    }

    /**
     * Format seconds as HH:MM:SS without wrapping after 24 hours.
     */
    private static function formatDuration(int|string|null $seconds): string
    {
        $seconds = max(0, (int) $seconds);

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $remainingSeconds,
        );
    }
}
