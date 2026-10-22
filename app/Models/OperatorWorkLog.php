<?php

namespace App\Models;

use App\Enums\UserShift;
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
    'shift',
    'machine_id',
    'part_id',
    'setup_id',
    'part_setup_id',
    'machine_cycle_time',
    'actual_cycle_time',
    'quantity',
    'rejection_quantity',
    'production_seconds',
    'remarks',
])]
class OperatorWorkLog extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [];

    protected array $filterable = [
        'user_id',
        'shift',
        'machine_id',
        'part_id',
        'setup_id',
        'part_setup_id',
    ];

    protected array $dateFilterable = [
        'work_date',
    ];

    protected array $sortable = [
        'work_date',
        'user_id',
        'shift',
        'machine_id',
        'part_id',
        'setup_id',
        'part_setup_id',
        'quantity',
        'rejection_quantity',
        'production_seconds',
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
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function setup(): BelongsTo
    {
        return $this->belongsTo(Setup::class);
    }

    public function partSetup(): BelongsTo
    {
        return $this->belongsTo(PartSetup::class);
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

    public function scopeForMachine(
        Builder $query,
        int $machineId,
    ): Builder {
        return $query->where(
            $this->qualifyColumn('machine_id'),
            $machineId,
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

    public function scopeForSetup(
        Builder $query,
        int $setupId,
    ): Builder {
        return $query->where(
            $this->qualifyColumn('setup_id'),
            $setupId,
        );
    }

    public function scopeForShift(
        Builder $query,
        string $shift,
    ): Builder {
        return $query->where(
            $this->qualifyColumn('shift'),
            $shift,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Production
    |--------------------------------------------------------------------------
    */

    public function calculateProductionSeconds(): int
    {
        return $this->quantity * $this->actual_cycle_time;
    }

    public function hasRejections(): bool
    {
        return $this->rejection_quantity > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'shift' => UserShift::class,
            'work_date' => 'date',
            'machine_cycle_time' => 'integer',
            'actual_cycle_time' => 'integer',
            'quantity' => 'integer',
            'rejection_quantity' => 'integer',
            'production_seconds' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Date Mutators
    |--------------------------------------------------------------------------
    */

    protected function setWorkDateAttribute($value): void
    {
        $this->attributes['work_date'] = $value !== null
            ? Carbon::parse($value)->toDateString()
            : null;
    }
}
