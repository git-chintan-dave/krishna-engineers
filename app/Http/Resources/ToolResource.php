<?php

namespace App\Http\Resources;

use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Tool
 */
class ToolResource extends JsonResource
{
    /*
    |--------------------------------------------------------------------------
    | Resource
    |--------------------------------------------------------------------------
    */

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'image' => $this->image
                ? Storage::disk('public')->url(
                    'tools/' . $this->image,
                )
                : null,
            'quantity' => $this->quantity,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }
}
