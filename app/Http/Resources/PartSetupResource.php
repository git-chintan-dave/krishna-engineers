<?php

namespace App\Http\Resources;

use App\Models\PartSetup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin PartSetup
 */
class PartSetupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'part_id' => $this->part_id,
            'part' => $this->whenLoaded(
                'part',
                fn() => [
                    'id' => $this->part->id,
                    'name' => $this->part->name,
                    'reference_name' => $this->part->reference_name,
                ],
            ),
            'setup_id' => $this->setup_id,
            'setup' => $this->whenLoaded(
                'setup',
                fn() => [
                    'id' => $this->setup->id,
                    'name' => $this->setup->name,
                ],
            ),
            'image' => $this->image
                ? Storage::disk('public')->url(
                    'part-setups/' . $this->image,
                )
                : null,
            'machine_cycle_time' => $this->machine_cycle_time_seconds,
            'machine_cycle_time_formatted' => gmdate(
                'H:i:s',
                $this->machine_cycle_time_seconds,
            ),
            'actual_cycle_time' => $this->actual_cycle_time_seconds,
            'actual_cycle_time_formatted' => gmdate(
                'H:i:s',
                $this->actual_cycle_time_seconds,
            ),
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }
}
