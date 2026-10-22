<?php

namespace App\Models;

use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        'section_size_id',
    ];

    protected array $dateFilterable = [
        'work_date',
    ];

    protected string $startDateFilterColumn = 'work_date';

    protected string $endDateFilterColumn = 'work_date';

    protected array $sortable = [
        'work_date',
        'user.name',
        'part.name',
        'section.name',
        'section_size_id',
        'quantity',
    ];

    protected string $defaultSort = 'work_date';

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
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function sectionSize(): BelongsTo
    {
        return $this->belongsTo(SectionSize::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForUser(
        Builder $query,
        int $userId,
    ): Builder {
        return $query->where(
            $this->qualifyColumn('user_id'),
            $userId,
        );
    }

    public function scopeForDate(
        Builder $query,
        $date,
    ): Builder {
        return $query->whereDate(
            $this->qualifyColumn('work_date'),
            $date,
        );
    }

    public function scopeFromDate(
        Builder $query,
        $date,
    ): Builder {
        return $query->whereDate(
            $this->qualifyColumn('work_date'),
            '>=',
            $date,
        );
    }

    public function scopeToDate(
        Builder $query,
        $date,
    ): Builder {
        return $query->whereDate(
            $this->qualifyColumn('work_date'),
            '<=',
            $date,
        );
    }

    public function scopeBetweenDates(
        Builder $query,
        $fromDate,
        $toDate,
    ): Builder {
        return $query->whereBetween(
            $this->qualifyColumn('work_date'),
            [
                $fromDate,
                $toDate,
            ],
        );
    }

    public function scopeForPart(
        Builder $query,
        int $partId,
    ): Builder {
        return $query->where(
            $this->qualifyColumn('part_id'),
            $partId,
        );
    }

    public function scopeForSectionSize(
        Builder $query,
        int $sectionSizeId,
    ): Builder {
        return $query->where(
            $this->qualifyColumn('section_size_id'),
            $sectionSizeId,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Date Handling
    |--------------------------------------------------------------------------
    */

    protected function setWorkDateAttribute($value): void
    {
        $this->attributes['work_date'] = $value !== null
            ? Carbon::parse($value)->toDateString()
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'quantity' => 'integer',
        ];
    }
}
