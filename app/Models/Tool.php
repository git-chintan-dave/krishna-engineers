<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\ToolType;
use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'type',
    'image',
    'quantity',
    'status',
])]
class Tool extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [
        'name',
        'quantity',
    ];

    protected array $filterable = [
        'type',
        'status',
    ];

    protected array $sortable = [
        'id',
        'name',
        'type',
        'quantity',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(ToolTransaction::class);
    }

    // Relationships

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
            'type' => ToolType::class,
            'status' => Status::class,
            'quantity' => 'integer',
        ];
    }
}
