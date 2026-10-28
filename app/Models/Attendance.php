<?php

namespace App\Models;

use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'attendance_date',
    'start_at',
    'end_at',
    'total_working_seconds',
    'break_seconds',
    'effective_working_seconds',
    'undertime_seconds',
])]
class Attendance extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [];

    protected array $filterable = [
        'user_id',
        'attendance_date',
    ];

    protected string $startDateFilterColumn = 'attendance_date';

    protected string $endDateFilterColumn = 'attendance_date';

    protected array $sortable = [
        'id',
        'attendance_date',
        'start_at',
        'end_at',
        'break_seconds',
        'effective_working_seconds',
        'undertime_seconds',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'desc';

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
        return $this->end_at === null && $this->start_at->toDateString() === now()->toDateString();
    }

    public function needsAttention(): bool
    {
        return $this->start_at !== null && blank($this->end_at) && $this->start_at->toDateString() !== now()->toDateString();
    }

    // Helpers

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relationships

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class)
            ->orderBy('start_at');
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'total_working_seconds' => 'integer',
            'break_seconds' => 'integer',
            'effective_working_seconds' => 'integer',
        ];
    }

    public function scopeForUser(Builder $query, int $userId): Builder {
        return $query->where('user_id', $userId);
    }
}
