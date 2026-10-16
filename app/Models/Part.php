<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'new_part_name',
    'section_id',
    'image',
    'cutting_size',
    'part_weight',
    'raw_material_weight',
    'status',
])]
class Part extends Model
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Casts
    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    // Relationships
    public function partSetups(): HasMany
    {
        return $this->hasMany(PartSetup::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === Status::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === Status::INACTIVE;
    }
}
