<?php

namespace App\Http\Resources;

use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Part
 */
class PartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'new_part_name' => $this->new_part_name,
            'section_id' => $this->section_id,
            'section' => [
                'id' => $this->section?->id,
                'name' => $this->section?->name,
            ],
            'cutting_size' => $this->cutting_size,
            'part_weight' => $this->part_weight,
            'raw_material_weight' => $this->raw_material_weight,
            'image' => $this->image
                ? Storage::disk('public')->url(
                    'parts/'.$this->image,
                )
                : null,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }
}
