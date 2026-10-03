<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded(
                'user',
                fn (): array => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'employee_code' => $this->user->employee_code,
                ],
            ),
            'attendance_date' => $this->attendance_date?->toDateString(),
            'attendance_date_formatted' => $this->attendance_date?->format('d M Y'),
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'start_at_formatted' => $this->start_at?->format('h:i A'),
            'end_at_formatted' => $this->end_at?->format('h:i A'),
            'is_open' => $this->end_at === null,
            'total_working_seconds' => $this->total_working_seconds,
            'break_seconds' => $this->break_seconds,
            'effective_working_seconds' => $this->effective_working_seconds,
            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks'),
            ),
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }
}
