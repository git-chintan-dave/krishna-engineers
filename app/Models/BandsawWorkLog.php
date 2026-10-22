<?php

namespace App\Models;

use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'work_date',
    'part_id',
    'section_size_id',
    'quantity',
    'remarks',
])]
class BandsawWorkLog extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [];

    protected array $filterable = [
        'user_id',
        'part_id',
        'section_id',
        'section_size_id',
    ];

    protected array $dateFilterable = [
        'work_date',
    ];

    protected array $sortable = [
        'work_date',
        'user.name',
        'part.name',
        'section.name',
        'section_size_id',
        'quantity',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'desc';

    /*
    |--------------------------------------------------------------------------
    | UUID
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function sectionSize(): BelongsTo
    {
        return $this->belongsTo(SectionSize::class);
    }

    public function scopeForUser(
        Builder $query,
        int     $userId,
    ): Builder
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForDate(
        Builder $query,
                $date,
    ): Builder
    {
        return $query->whereDate(
            'work_date',
            $date,
        );
    }

    public function scopeFromDate(
        Builder $query,
                $date,
    ): Builder
    {
        return $query->whereDate(
            'work_date',
            '>=',
            $date,
        );
    }

    public function scopeToDate(
        Builder $query,
                $date,
    ): Builder
    {
        return $query->whereDate(
            'work_date',
            '<=',
            $date,
        );
    }

    public function scopeBetweenDates(
        Builder $query,
                $fromDate,
                $toDate,
    ): Builder
    {
        return $query->whereBetween(
            'work_date',
            [
                $fromDate,
                $toDate,
            ],
        );
    }

    public function scopeForPart(
        Builder $query,
        int     $partId,
    ): Builder
    {
        return $query->where(
            'part_id',
            $partId,
        );
    }

    public function scopeForSectionSize(
        Builder $query,
        int     $sectionSizeId,
    ): Builder
    {
        return $query->where(
            'section_size_id',
            $sectionSizeId,
        );
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'quantity' => 'integer',
        ];
    }
}
