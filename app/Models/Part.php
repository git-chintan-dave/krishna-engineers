<?php

namespace App\Models;

use App\Enums\Status;
use App\Traits\HasFilter;
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
    use HasFilter, HasUuids;

    protected array $searchable = [
        'name',
        'new_part_name',
    ];

    protected array $filterable = [
        'section_id',
        'status',
    ];

    protected array $sortable = [
        'id',
        'name',
        'new_part_name',
        'cutting_size',
        'part_weight',
        'raw_material_weight',
        'status',
        'created_at',
        'section.name',
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

    public function partSetups(): HasMany
    {
        return $this->hasMany(PartSetup::class);
    }

    // Relationships

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function isActive(): bool
    {
        return $this->status === Status::ACTIVE;
    }

    // Helpers

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
