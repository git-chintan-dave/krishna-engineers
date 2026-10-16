<?php

namespace App\Http\Resources;

use App\Models\OperatorWorkLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OperatorWorkLog
 */
class OperatorWorkLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dailyProductionSeconds = (int) ($this->daily_production_seconds ?? 0);
        $effectiveWorkingSeconds = $this->effective_working_seconds !== null
            ? (int) $this->effective_working_seconds
            : null;

        $productionEfficiency = $effectiveWorkingSeconds !== null
        && $effectiveWorkingSeconds > 0
            ? round(
                ($dailyProductionSeconds / $effectiveWorkingSeconds) * 100,
                2,
            )
            : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'work_date' => $this->work_date?->toDateString(),
            'shift' => $this->shift,

            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded(
                    'user',
                    fn() => $this->user->name,
                ),
                'employee_code' => $this->whenLoaded(
                    'user',
                    fn() => $this->user->employee_code,
                ),
            ],

            'machine' => [
                'id' => $this->machine_id,
                'name' => $this->whenLoaded(
                    'machine',
                    fn() => $this->machine->name,
                ),
                'reference_name' => $this->whenLoaded(
                    'machine',
                    fn() => $this->machine->reference_name,
                ),
            ],

            'part' => [
                'id' => $this->part_id,
                'name' => $this->whenLoaded(
                    'part',
                    fn() => $this->part->name,
                ),
                'reference_name' => $this->whenLoaded(
                    'part',
                    fn() => $this->part->reference_name,
                ),
            ],

            'setup' => [
                'id' => $this->setup_id,
                'name' => $this->whenLoaded(
                    'setup',
                    fn() => $this->setup->name,
                ),
            ],

            'part_setup_id' => $this->part_setup_id,

            'machine_cycle_time' => $this->machine_cycle_time,
            'actual_cycle_time' => $this->actual_cycle_time,

            'quantity' => $this->quantity,
            'rejection_quantity' => $this->rejection_quantity,

            'production_seconds' => $this->production_seconds,
            'production_seconds_formatted' => $this->formatDuration(
                (int) $this->production_seconds,
            ),

            'daily_production_seconds' => $dailyProductionSeconds,
            'daily_production_seconds_formatted' => $this->formatDuration(
                $dailyProductionSeconds,
            ),

            'effective_working_seconds' => $effectiveWorkingSeconds,
            'production_efficiency' => $productionEfficiency,

            'remarks' => $this->remarks,

            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
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
