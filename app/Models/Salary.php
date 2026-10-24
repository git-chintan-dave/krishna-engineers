<?php

namespace App\Models;

use App\Enums\SalaryStatus;
use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'salary_period_start',
    'salary_period_end',
    'base_salary',
    'attendance_days',
    'total_undertime_seconds',
    'late_days',
    'working_days',
    'upad',
    'paid_loan',
    'incentive_rate',
    'incentive',
    'deduction_rate',
    'deduction',
    'adjustment',
    'adjustment_reason',
    'final_payable',
    'status',
    'paid_at',
])]
class Salary extends Model
{
    use HasFilter, HasUuids;

    protected array $searchable = [];

    protected array $filterable = [
        'user_id',
        'status',
    ];

    protected array $sortable = [
        'id',
        'salary_period_start',
        'salary_period_end',
        'working_days',
        'base_salary',
        'incentive',
        'deduction',
        'final_payable',
        'status',
        'user.name',
        'created_at',
    ];

    protected string $startDateFilterColumn = 'salary_period_start';

    protected string $endDateFilterColumn = 'salary_period_end';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relationships

    public function isDraft(): bool
    {
        return $this->status === SalaryStatus::DRAFT;
    }

    // Status Helpers

    public function isPaid(): bool
    {
        return $this->status === SalaryStatus::PAID;
    }

    protected function casts(): array
    {
        return [
            'salary_period_start' => 'date',
            'salary_period_end' => 'date',

            'base_salary' => 'decimal:2',
            'working_days' => 'integer',

            'upad' => 'decimal:2',
            'paid_loan' => 'decimal:2',

            'incentive_rate' => 'decimal:2',
            'incentive' => 'decimal:2',

            'deduction_rate' => 'decimal:2',
            'deduction' => 'decimal:2',

            'adjustment' => 'decimal:2',
            'final_payable' => 'decimal:2',

            'status' => SalaryStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
