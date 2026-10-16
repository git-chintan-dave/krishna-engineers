<?php

namespace App\Http\Resources;

use App\Models\BandsawWorkLog;
use App\Models\RawMaterialTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RawMaterialTransaction
 */
class RawMaterialTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'section_size' => [
                'id' => $this->section_size_id,
                'section_id' => $this->when(
                    $this->relationLoaded('sectionSize'),
                    fn() => $this->sectionSize?->section_id,
                ),
                'size' => $this->whenLoaded(
                    'sectionSize',
                    fn() => $this->sectionSize?->size,
                ),
                'section' => $this->when(
                    $this->relationLoaded('sectionSize')
                    && $this->sectionSize?->relationLoaded('section'),
                    fn() => $this->sectionSize->section?->name,
                ),
            ],

            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->label(),
            ],

            'quantity' => $this->quantity,

            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded(
                    'user',
                    fn() => $this->user?->name,
                ),
                'employee_code' => $this->whenLoaded(
                    'user',
                    fn() => $this->user?->employee_code,
                ),
            ],

            'reference' => $this->when(
                $this->relationLoaded('reference'),
                fn() => $this->reference
                    ? [
                        'id' => $this->reference->getKey(),
                        'label' => $this->referenceLabel(),
                    ]
                    : null,
            ),

            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->format('d M Y'),
        ];
    }

    private function referenceLabel(): string
    {
        return match (true) {
            $this->reference instanceof BandsawWorkLog => "Bandsaw Work Log #{$this->reference->getKey()}",

            default => "Reference #{$this->reference->getKey()}",
        };
    }
}
