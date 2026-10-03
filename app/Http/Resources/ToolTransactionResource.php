<?php

namespace App\Http\Resources;

use App\Models\ToolTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ToolTransaction
 */
class ToolTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tool' => [
                'id' => $this->tool_id,
                'name' => $this->whenLoaded(
                    'tool',
                    fn () => $this->tool?->name
                ),
                'type' => $this->whenLoaded(
                    'tool',
                    fn () => $this->tool?->type?->value
                ),
            ],
            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->label(),
            ],
            'quantity' => $this->quantity,
            'from_user' => [
                'id' => $this->from_user_id,
                'name' => $this->whenLoaded(
                    'fromUser',
                    fn () => $this->fromUser?->name
                ),
                'employee_code' => $this->whenLoaded(
                    'fromUser',
                    fn () => $this->fromUser?->employee_code
                ),
            ],
            'to_user' => [
                'id' => $this->to_user_id,
                'name' => $this->whenLoaded(
                    'toUser',
                    fn () => $this->toUser?->name
                ),
                'employee_code' => $this->whenLoaded(
                    'toUser',
                    fn () => $this->toUser?->employee_code
                ),
            ],
            'part' => [
                'id' => $this->part_id,
                'name' => $this->whenLoaded(
                    'part',
                    fn () => $this->part?->name
                ),
                'reference_name' => $this->whenLoaded(
                    'part',
                    fn () => $this->part?->reference_name
                ),
            ],
            'setup' => [
                'id' => $this->setup_id,
                'name' => $this->whenLoaded(
                    'setup',
                    fn () => $this->setup?->name
                ),
            ],
            'return_scrap' => $this->return_scrap,
            'created_by' => [
                'id' => $this->created_by_user_id,
                'name' => $this->whenLoaded(
                    'createdBy',
                    fn () => $this->createdBy?->name
                ),
                'employee_code' => $this->whenLoaded(
                    'createdBy',
                    fn () => $this->createdBy?->employee_code
                ),
            ],
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->format('d M Y H:i'),
            'updated_at' => $this->updated_at?->format('d M Y H:i'),
        ];
    }
}
