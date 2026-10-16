<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'attendance_id',
    'start_at',
    'end_at',
    'duration_seconds',
])]
class AttendanceBreak extends Model
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

    public function isOpen(): bool
    {
        return $this->end_at === null;
    }

    // Helpers

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    // Relationships

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }
}
