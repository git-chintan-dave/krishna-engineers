<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\ToolType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'type',
    'image',
    'quantity',
    'status',
])]
class Tool extends Model
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
            'type' => ToolType::class,
            'status' => Status::class,
            'quantity' => 'integer',
        ];
    }

    // Relationships
    public function transactions(): HasMany
    {
        return $this->hasMany(ToolTransaction::class);
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
