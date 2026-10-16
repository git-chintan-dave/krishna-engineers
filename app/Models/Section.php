<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'status',
])]
class Section extends Model
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
    public function sectionSizes(): HasMany
    {
        return $this->hasMany(SectionSize::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    // Status Helpers
    public function isActive(): bool
    {
        return $this->status === Status::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === Status::INACTIVE;
    }
}
