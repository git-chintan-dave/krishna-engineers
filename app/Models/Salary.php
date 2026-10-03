<?php

namespace App\Models;

use App\Enums\SalaryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'salary_period_start',
    'salary_period_end',
    'base_salary',
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

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Status Helpers
    public function isDraft(): bool
    {
        return $this->status === SalaryStatus::DRAFT;
    }

    public function isPaid(): bool
    {
        return $this->status === SalaryStatus::PAID;
    }
}
