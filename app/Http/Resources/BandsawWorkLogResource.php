<?php

namespace App\Http\Resources;

use App\Models\BandsawWorkLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BandsawWorkLog
 */
class BandsawWorkLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'work_date' => $this->work_date?->toDateString(),

            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded(
                    'user',
                    fn () => $this->user->name,
                ),
                'employee_code' => $this->whenLoaded(
                    'user',
                    fn () => $this->user->employee_code,
                ),
            ],

            'part' => [
                'id' => $this->part_id,
                'name' => $this->whenLoaded(
                    'part',
                    fn () => $this->part->name,
                ),
                'reference_name' => $this->whenLoaded(
                    'part',
                    fn () => $this->part->reference_name,
                ),
            ],

            'section' => [
                'id' => $this->whenLoaded(
                    'sectionSize',
                    fn () => $this->sectionSize?->section_id,
                ),
                'name' => $this->whenLoaded(
                    'sectionSize',
                    fn () => $this->sectionSize?->section?->name,
                ),
            ],

            'section_size' => [
                'id' => $this->section_size_id,
                'size' => $this->whenLoaded(
                    'sectionSize',
                    fn () => $this->sectionSize?->size,
                ),
            ],

            'quantity' => $this->quantity,
            'remarks' => $this->remarks,

            'created_at' => $this->created_at?->format('d M Y'),

            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }
}
