<?php

namespace App\Models;

use App\Enums\RawMaterialTransactionType;
use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
    use HasFilter, HasUuids;

    protected array $searchable = [];

    protected array $filterable = [
        'section_id' => 'sectionSize.section_id',
        'section_size_id',
        'type',
    ];

    protected array $sortable = [
        'id',
        'sectionSize.section.name',
        'sectionSize.size',
        'type',
        'quantity',
        'user.name',
        'created_at',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'desc';

    protected string $startDateFilterColumn = 'created_at';

    protected string $endDateFilterColumn = 'created_at';

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    public function sectionSize(): BelongsTo
    {
        return $this->belongsTo(SectionSize::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForSectionSize(
        Builder $query,
        int     $sectionSizeId,
    ): Builder
    {
        return $query->where('section_size_id', $sectionSizeId);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOfType(
        Builder                    $query,
        RawMaterialTransactionType $type,
    ): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForUser(
        Builder $query,
        int     $userId,
    ): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFromDate(
        Builder $query,
        string  $date,
    ): Builder
    {
        return $query->whereDate('created_at', '>=', $date);
    }

    public function scopeToDate(
        Builder $query,
        string  $date,
    ): Builder
    {
        return $query->whereDate('created_at', '<=', $date);
    }

    public function scopeBetweenDates(
        Builder $query,
        string  $fromDate,
        string  $toDate,
    ): Builder
    {
        return $query->whereBetween('created_at', [
            "{$fromDate} 00:00:00",
            "{$toDate} 23:59:59.999999",
        ]);
    }

    protected function casts(): array
    {
        return [
            'type' => RawMaterialTransactionType::class,
            'quantity' => 'integer',
            'reference_id' => 'integer',
        ];
    }
}
