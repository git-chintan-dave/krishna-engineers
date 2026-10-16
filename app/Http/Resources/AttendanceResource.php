<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\OperatorProductionEfficiencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $productionEfficiencyService = app(
            OperatorProductionEfficiencyService::class,
        );

        $productionSeconds = $productionEfficiencyService->getProductionSecondsForDay(
            $this->user_id,
            $this->attendance_date,
        );

        $productionEfficiency = $productionEfficiencyService->calculate(
            $productionSeconds,
            $this->effective_working_seconds,
        );

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
                fn(): array => [
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

            'attendance_date_formatted' => $this->attendance_date?->format('d M Y'),

            'start_at' => $this->start_at?->format('Y-m-d H:i:s'),

            'end_at' => $this->end_at?->format('Y-m-d H:i:s'),

            'start_at_formatted' => $this->start_at?->format('h:i A'),

            'end_at_formatted' => $this->end_at?->format('h:i A'),

            'is_open' => $this->isOpen(),

            /*
            |--------------------------------------------------------------------------
            | Working Time
            |--------------------------------------------------------------------------
            */

            'total_working_seconds' => $this->total_working_seconds,

            'total_working_seconds_formatted' => Carbon::createFromTimestampUTC(
                $this->total_working_seconds,
            )->format('H:i:s'),

            'break_seconds' => $this->break_seconds,

            'break_seconds_formatted' => Carbon::createFromTimestampUTC(
                $this->break_seconds,
            )->format('H:i:s'),

            'effective_working_seconds' => $this->effective_working_seconds,

            'effective_working_seconds_formatted' => Carbon::createFromTimestampUTC(
                $this->effective_working_seconds,
            )->format('H:i:s'),

            /*
            |--------------------------------------------------------------------------
            | Production
            |--------------------------------------------------------------------------
            */

            'production_seconds' => $productionSeconds,

            'production_seconds_formatted' => Carbon::createFromTimestampUTC(
                $productionSeconds,
            )->format('H:i:s'),

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

            'is_locked' => app(AttendanceService::class)->isLocked(
                $this->resource,
            ),
        ];
    }
}
