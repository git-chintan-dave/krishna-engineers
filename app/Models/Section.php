<?php

namespace App\Models;

use App\Enums\Status;
use App\Traits\HasFilter;
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
    use HasFilter, HasUuids;

    protected array $searchable = [
        'name',
    ];

    protected array $filterable = [
        'status',
    ];

    protected array $sortable = [
        'id',
        'name',
        'status',
        'created_at',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'desc';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Casts

    public function sectionSizes(): HasMany
    {
        return $this->hasMany(SectionSize::class);
    }

    // Relationships

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    public function isActive(): bool
    {
        return $this->status === Status::ACTIVE;
    }

    // Status Helpers

    public function isInactive(): bool
    {
        return $this->status === Status::INACTIVE;
    }

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }
}
