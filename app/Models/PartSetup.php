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

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    // Relationships

    public function setup(): BelongsTo
    {
        return $this->belongsTo(Setup::class);
    }

    public function hasImage(): bool
    {
        return filled($this->image);
    }

    // Helpers

    public function hasActualCycleTime(): bool
    {
        return $this->actual_cycle_time_seconds > 0;
    }

    public function cycleTimeDifference(): string
    {
        return gmdate('H:i:s', abs($this->cycleTimeDifferenceSeconds()));
    }

    public function cycleTimeDifferenceSeconds(): int
    {
        return $this->actual_cycle_time_seconds - $this->machine_cycle_time_seconds;
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

    protected function casts(): array
    {
        return [
            'machine_cycle_time_seconds' => 'integer',
            'actual_cycle_time_seconds' => 'integer',
        ];
    }

    protected function machineCycleTime(): Attribute
    {
        return Attribute::get(
            fn() => gmdate('H:i:s', $this->machine_cycle_time_seconds)
        );
    }

    protected function actualCycleTime(): Attribute
    {
        return Attribute::get(
            fn() => gmdate('H:i:s', $this->actual_cycle_time_seconds)
        );
    }
}
