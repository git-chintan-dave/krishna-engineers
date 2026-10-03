<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'status',
])]
class Setup extends Model
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
