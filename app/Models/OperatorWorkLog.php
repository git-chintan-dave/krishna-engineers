<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    use HasUuids;

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

    public function scopeForUser(
        Builder $query,
        int     $userId
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
        string  $date
    ): Builder
    {
        return $query->whereDate('work_date', $date);
    }

    public function scopeFromDate(
        Builder $query,
        string  $date
    ): Builder
    {
        return $query->whereDate('work_date', '>=', $date);
    }

    public function scopeToDate(
        Builder $query,
        string  $date
    ): Builder
    {
        return $query->whereDate('work_date', '<=', $date);
    }

    public function scopeBetweenDates(
        Builder $query,
        string  $from,
        string  $to
    ): Builder
    {
        return $query->whereBetween('work_date', [$from, $to]);
    }

    public function scopeForMachine(
        Builder $query,
        int     $machineId
    ): Builder
    {
        return $query->where('machine_id', $machineId);
    }

    public function scopeForPart(
        Builder $query,
        int     $partId
    ): Builder
    {
        return $query->where('part_id', $partId);
    }

    public function scopeForSetup(
        Builder $query,
        int     $setupId
    ): Builder
    {
        return $query->where('setup_id', $setupId);
    }

    public function scopeForShift(
        Builder $query,
        string  $shift
    ): Builder
    {
        return $query->where('shift', $shift);
    }

    public function calculateProductionSeconds(): int
    {
        return $this->quantity * $this->actual_cycle_time;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function hasRejections(): bool
    {
        return $this->rejection_quantity > 0;
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'machine_cycle_time' => 'integer',
            'actual_cycle_time' => 'integer',
            'quantity' => 'integer',
            'rejection_quantity' => 'integer',
            'production_seconds' => 'integer',
        ];
    }
}
