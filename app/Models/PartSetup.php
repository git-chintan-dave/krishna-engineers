<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'part_id',
    'setup_id',
    'image',
    'machine_cycle_time_seconds',
    'actual_cycle_time_seconds',
])]
class PartSetup extends Model
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
            'machine_cycle_time_seconds' => 'integer',
            'actual_cycle_time_seconds' => 'integer',
        ];
    }

    // Relationships
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function setup(): BelongsTo
    {
        return $this->belongsTo(Setup::class);
    }

    // Helpers
    public function hasImage(): bool
    {
        return filled($this->image);
    }

    public function hasActualCycleTime(): bool
    {
        return $this->actual_cycle_time_seconds > 0;
    }

    protected function machineCycleTime(): Attribute
    {
        return Attribute::get(
            fn () => gmdate('H:i:s', $this->machine_cycle_time_seconds)
        );
    }

    protected function actualCycleTime(): Attribute
    {
        return Attribute::get(
            fn () => gmdate('H:i:s', $this->actual_cycle_time_seconds)
        );
    }

    public function cycleTimeDifferenceSeconds(): int
    {
        return $this->actual_cycle_time_seconds - $this->machine_cycle_time_seconds;
    }

    public function cycleTimeDifference(): string
    {
        return gmdate('H:i:s', abs($this->cycleTimeDifferenceSeconds()));
    }

    public function isFasterThanMachineCycle(): bool
    {
        return $this->actual_cycle_time_seconds > 0
            && $this->actual_cycle_time_seconds < $this->machine_cycle_time_seconds;
    }

    public function isSlowerThanMachineCycle(): bool
    {
        return $this->actual_cycle_time_seconds > $this->machine_cycle_time_seconds;
    }
}
