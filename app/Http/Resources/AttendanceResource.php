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
        $isMissing = (bool) ($this->is_missing ?? false);

        $attendanceType = $this->attendance_type ?? ($isMissing ? 'on_leave' : 'attendance');

        $dayStatus = $this->day_status
            ?? match ($attendanceType) {
                'week_off' => 'Week Off',
                'on_leave' => 'On Leave',
                default => 'Attendance',
            };

        $productionSeconds = 0;
        $productionEfficiency = 0;
        $isLocked = false;
        $isOpen = false;
        $needsAttention = false;

        if (! $isMissing) {
            $productionEfficiencyService = app(
                OperatorProductionEfficiencyService::class,
            );

            $productionSeconds = (int) $productionEfficiencyService
                ->getProductionSecondsForDay(
                    $this->user_id,
                    $this->attendance_date,
                );

            $productionEfficiency = $productionEfficiencyService->calculate(
                $productionSeconds,
                (int) $this->effective_working_seconds,
            );

            $isOpen = $this->isOpen();
            $needsAttention = $this->needsAttention();

            $isLocked = app(AttendanceService::class)->isLocked(
                $this->resource,
            );
        }

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

            'attendance_type' => $attendanceType,

            'day_status' => $dayStatus,

            'is_missing' => $isMissing,

            'start_at' => $startAt?->format('Y-m-d H:i:s'),

            'end_at' => $endAt?->format('Y-m-d H:i:s'),

            'start_at_formatted' => $startAt?->format('h:i A'),

            'end_at_formatted' => $endAt?->format('h:i A'),

            'is_open' => $isOpen,

            'needs_attention' => $needsAttention,

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

            'is_locked' => $isLocked,
        ];
    }

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
