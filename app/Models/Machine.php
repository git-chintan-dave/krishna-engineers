<?php

namespace App\Models;

use App\Enums\Status;
use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'machine_number',
    'status',
])]
class Machine extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [
        'name',
        'machine_number',
    ];

    protected array $filterable = [
        'status',
    ];

    protected array $sortable = [
        'id',
        'name',
        'machine_number',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::ACTIVE);
    }

    // Scopes

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', Status::INACTIVE);
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
