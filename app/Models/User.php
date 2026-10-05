<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name',
    'employee_code',
    'phone_number',
    'alternate_phone_number',
    'email',
    'joining_date',
    'password',
    'role',
    'shift',
    'salary',
    'incentive_per_day',
    'deduction_per_day',
    'aadhar_number',
    'status',
])]
#[Hidden([
    'password',
    'aadhar_number',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasUuids, TwoFactorAuthenticatable;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'shift' => UserShift::class,
            'status' => Status::class,
            'joining_date' => 'date',
            'salary' => 'decimal:2',
            'incentive_per_day' => 'decimal:2',
            'deduction_per_day' => 'decimal:2',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // Role Helpers

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === UserRole::SUPERVISOR;
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::OPERATOR;
    }

    public function isBandsawOperator(): bool
    {
        return $this->role === UserRole::BANDSAW;
    }

    public function isDeburringPackaging(): bool
    {
        return $this->role === UserRole::DEBURRING_PACKAGING;
    }

    // Status Helpers

    public function isActive(): bool
    {
        return $this->status === Status::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === Status::INACTIVE;
    }

    // Attendance Helpers

    public function attendanceForDate(CarbonInterface|string $date): ?Attendance
    {
        return $this->attendances()
            ->whereDate('attendance_date', $date)
            ->first();
    }

    // Salary Helpers

    public function salaryForPeriod(
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate
    ): ?Salary {
        return $this->salaries()
            ->where('salary_period_start', $startDate)
            ->where('salary_period_end', $endDate)
            ->first();
    }

    // Relationships

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }
}
