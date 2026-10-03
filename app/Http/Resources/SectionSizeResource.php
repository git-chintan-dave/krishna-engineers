<?php

namespace App\Http\Resources;

use App\Models\SectionSize;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SectionSize
 */
class SectionSizeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'section_id' => $this->section_id,
            'section' => [
                'id' => $this->section?->id,
                'name' => $this->section?->name,
            ],
            'size' => $this->size,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }
}
