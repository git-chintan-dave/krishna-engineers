<?php

namespace App\Http\Resources;

use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceBreak
 */
class AttendanceBreakResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'start_at' =>
                $this->start_at?->format('Y-m-d H:i:s'),

            'end_at' =>
                $this->end_at?->format('Y-m-d H:i:s'),

            'start_at_formatted' =>
                $this->start_at?->format('h:i A'),

            'end_at_formatted' =>
                $this->end_at?->format('h:i A'),

            'duration_seconds' =>
                $this->duration_seconds,

            'is_open' =>
                $this->end_at === null,
        ];
    }
}
