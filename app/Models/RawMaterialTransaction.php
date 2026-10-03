<?php

namespace App\Models;

use App\Enums\RawMaterialTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable([
    'section_size_id',
    'type',
    'quantity',
    'user_id',
    'reference_type',
    'reference_id',
    'remarks',
])]
class RawMaterialTransaction extends Model
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
            'type' => RawMaterialTransactionType::class,
            'quantity' => 'integer',
            'reference_id' => 'integer',
        ];
    }

    // Relationships
    public function sectionSize(): BelongsTo
    {
        return $this->belongsTo(SectionSize::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeForSectionSize(
        Builder $query,
        int $sectionSizeId
    ): Builder {
        return $query->where('section_size_id', $sectionSizeId);
    }

    public function scopeOfType(
        Builder $query,
        RawMaterialTransactionType $type
    ): Builder {
        return $query->where('type', $type->value);
    }

    public function scopeForUser(
        Builder $query,
        int $userId
    ): Builder {
        return $query->where('user_id', $userId);
    }

    public function scopeFromDate(
        Builder $query,
                $date
    ): Builder {
        return $query->whereDate('created_at', '>=', $date);
    }

    public function scopeToDate(
        Builder $query,
                $date
    ): Builder {
        return $query->whereDate('created_at', '<=', $date);
    }

    public function scopeBetweenDates(
        Builder $query,
                $fromDate,
                $toDate
    ): Builder {
        return $query->whereBetween('created_at', [$fromDate, $toDate]);
    }
}
