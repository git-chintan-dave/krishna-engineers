<?php

namespace App\Services;

use App\Enums\SalaryStatus;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SalaryService
{
    private const int SECONDS_PER_LATE_DAY = 12 * 60 * 60;

    /**
     * Generate draft salaries for all active employees for the
     * previous calendar month. Existing records are skipped.
     *
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     total_employees: int,
     *     generated: int,
     *     skipped: int,
     *     salaries: Collection<int, Salary>
     * }
     */
    public function generateForEmployees(
        ?int $userId = null,
        ?CarbonInterface $referenceDate = null,
    ): array {
        $referenceDate = $referenceDate
            ? Carbon::instance($referenceDate)
            : now();

        $periodStart = $referenceDate
            ->copy()
            ->subMonthNoOverflow()
            ->startOfMonth();

        $periodEnd = $periodStart->copy()->endOfMonth();

        return DB::transaction(function () use (
            $userId,
            $periodStart,
            $periodEnd,
        ): array {
            $employeesQuery = User::query()
                ->where('status', 'active');

            if ($userId !== null) {
                $employeesQuery->whereKey($userId);
            }

            $employees = $employeesQuery->get();

            $existingEmployeeIds = Salary::query()
                ->whereDate(
                    'salary_period_start',
                    $periodStart->toDateString(),
                )
                ->whereDate(
                    'salary_period_end',
                    $periodEnd->toDateString(),
                )
                ->whereIn('user_id', $employees->modelKeys())
                ->pluck('user_id')
                ->flip();

            $generated = collect();
            $skipped = 0;

            foreach ($employees as $employee) {
                if ($existingEmployeeIds->has($employee->id)) {
                    $skipped++;

                    continue;
                }

                $generated->push(
                    $this->generate(
                        user: $employee,
                        periodStart: $periodStart,
                        periodEnd: $periodEnd,
                    ),
                );
            }

            return [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_employees' => $employees->count(),
                'generated' => $generated->count(),
                'skipped' => $skipped,
                'salaries' => $generated,
            ];
        });
    }

    /**
     * Regenerate an existing unpaid salary using the latest employee
     * salary settings and attendance records.
     *
     * Preserve manually entered adjustments and the confirmed late days.
     */
    public function regenerate(Salary $salary): Salary
    {
        return DB::transaction(function () use ($salary): Salary {
            $salary = Salary::query()
                ->whereKey($salary->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($salary->isPaid()) {
                throw new RuntimeException(
                    'Paid salaries cannot be regenerated.',
                );
            }

            $salary->loadMissing('user');

            $attributes = $this->salaryCalculationAttributes(
                user: $salary->user,
                periodStart: $salary->salary_period_start,
                periodEnd: $salary->salary_period_end,
                lateDays: (int) $salary->late_days,
                upad: (float) $salary->upad,
                paidLoan: (float) $salary->paid_loan,
                adjustment: (float) $salary->adjustment,
            );

            $salary->update([
                ...$attributes,
                'adjustment_reason' => $salary->adjustment_reason,
            ]);

            return $salary->refresh();
        });
    }

    /**
     * Generate a salary record.
     *
     * When lateDays is null, use the automatically calculated value.
     * When supplied, use the client's confirmed late-day value.
     */
    public function generate(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        float $upad = 0,
        float $paidLoan = 0,
        float $adjustment = 0,
        ?string $adjustmentReason = null,
        ?int $lateDays = null,
    ): Salary {
        return DB::transaction(function () use (
            $user,
            $periodStart,
            $periodEnd,
            $upad,
            $paidLoan,
            $adjustment,
            $adjustmentReason,
            $lateDays,
        ): Salary {
            $existing = $user->salaries()
                ->whereDate(
                    'salary_period_start',
                    $periodStart->toDateString(),
                )
                ->whereDate(
                    'salary_period_end',
                    $periodEnd->toDateString(),
                )
                ->exists();

            if ($existing) {
                throw new RuntimeException(
                    'Salary for this period has already been generated.',
                );
            }

            $attributes = $this->salaryCalculationAttributes(
                user: $user,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                lateDays: $lateDays,
                upad: $upad,
                paidLoan: $paidLoan,
                adjustment: $adjustment,
            );

            try {
                return $user->salaries()->create([
                    'salary_period_start' => $periodStart->toDateString(),
                    'salary_period_end' => $periodEnd->toDateString(),

                    ...$attributes,

                    'adjustment_reason' => $adjustmentReason,
                    'status' => SalaryStatus::DRAFT,
                    'paid_at' => null,
                ]);
            } catch (QueryException $e) {
                if ($this->isUniqueViolation($e)) {
                    throw new RuntimeException(
                        'Salary for this period has already been generated.',
                        previous: $e,
                    );
                }

                throw new RuntimeException(
                    'Unable to generate salary. Please try again.',
                    previous: $e,
                );
            }
        });
    }

    /**
     * Calculate attendance days and total undertime for the period.
     *
     * Only attendance records with a start time are counted,
     * matching the existing working-day calculation.
     *
     * @return array{
     *     attendance_days: int,
     *     total_undertime_seconds: int,
     *     calculated_late_days: int,
     *     working_days: int
     * }
     */
    public function calculateAttendance(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): array {
        $attendances = $user->attendances()
            ->whereBetween('attendance_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->whereNotNull('start_at')
            ->get([
                'undertime_seconds',
            ]);

        $attendanceDays = $attendances->count();

        $totalUndertimeSeconds = (int) $attendances->sum(
            fn ($attendance): int => max(
                0,
                (int) $attendance->undertime_seconds,
            ),
        );

        $calculatedLateDays = intdiv(
            $totalUndertimeSeconds,
            self::SECONDS_PER_LATE_DAY,
        );

        return [
            'attendance_days' => $attendanceDays,
            'total_undertime_seconds' => $totalUndertimeSeconds,
            'calculated_late_days' => $calculatedLateDays,
            'working_days' => max(
                0,
                $attendanceDays - $calculatedLateDays,
            ),
        ];
    }

    /**
     * Calculate incentive using the existing rule.
     */
    public function calculateIncentive(
        int $workingDays,
        float $incentiveRate,
    ): float {
        if ($workingDays <= 23) {
            return 0;
        }

        return round(($workingDays - 23) * $incentiveRate, 2);
    }

    /**
     * Calculate deduction using the existing rule.
     */
    public function calculateDeduction(
        int $workingDays,
        float $deductionRate,
    ): float {
        if ($workingDays >= 21) {
            return 0;
        }

        return round((21 - $workingDays) * $deductionRate, 2);
    }

    /**
     * Calculate final payable salary.
     *
     * baseSalary is the per-day base salary.
     */
    public function calculateFinalPayable(
        float $baseSalary,
        int $workingDays,
        float $incentive,
        float $adjustment,
        float $upad,
        float $paidLoan,
        float $deduction,
    ): float {
        return round(
            ($baseSalary * $workingDays)
            + $incentive
            + $adjustment
            - $upad
            - $paidLoan
            - $deduction,
            2,
        );
    }

    /**
     * Build all salary calculation attributes.
     *
     * The selected lateDays value is persisted as the client's
     * confirmed value. It is not rounded or recalculated after saving.
     *
     * @return array<string, int|float>
     */
    private function salaryCalculationAttributes(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?int $lateDays,
        float $upad,
        float $paidLoan,
        float $adjustment,
    ): array {
        $attendance = $this->calculateAttendance(
            $user,
            $periodStart,
            $periodEnd,
        );

        $attendanceDays = $attendance['attendance_days'];

        $lateDays ??= $attendance['calculated_late_days'];

        $this->validateLateDays($attendanceDays, $lateDays);

        $workingDays = $attendanceDays - $lateDays;

        $baseSalary = (float) $user->salary;
        $incentiveRate = (float) $user->incentive_per_day;
        $deductionRate = (float) $user->deduction_per_day;

        $incentive = $this->calculateIncentive(
            $workingDays,
            $incentiveRate,
        );

        $deduction = $this->calculateDeduction(
            $workingDays,
            $deductionRate,
        );

        $finalPayable = $this->calculateFinalPayable(
            baseSalary: $baseSalary,
            workingDays: $workingDays,
            incentive: $incentive,
            adjustment: $adjustment,
            upad: $upad,
            paidLoan: $paidLoan,
            deduction: $deduction,
        );

        return [
            'attendance_days' => $attendanceDays,
            'total_undertime_seconds' => $attendance['total_undertime_seconds'],
            'late_days' => $lateDays,
            'working_days' => $workingDays,

            'base_salary' => $baseSalary,

            'incentive_rate' => $incentiveRate,
            'incentive' => $incentive,

            'deduction_rate' => $deductionRate,
            'deduction' => $deduction,

            'upad' => $upad,
            'paid_loan' => $paidLoan,
            'adjustment' => $adjustment,

            'final_payable' => $finalPayable,
        ];
    }

    /**
     * Ensure late days cannot exceed attendance days.
     */
    private function validateLateDays(
        int $attendanceDays,
        int $lateDays,
    ): void {
        if ($lateDays < 0 || $lateDays > $attendanceDays) {
            throw ValidationException::withMessages([
                'late_days' => sprintf(
                    'Late days must be between 0 and %d.',
                    $attendanceDays,
                ),
            ]);
        }
    }

    /**
     * Determine whether the exception represents a unique violation.
     */
    private function isUniqueViolation(
        QueryException $exception,
    ): bool {
        return $exception->getCode() === '23000';
    }

    /**
     * Recalculate a draft salary.
     *
     * Pass lateDays to apply the client's edited value.
     * If omitted, retain the previously saved late-day value.
     */
    public function recalculate(
        Salary $salary,
        float $upad = 0,
        float $paidLoan = 0,
        float $adjustment = 0,
        ?string $adjustmentReason = null,
        ?int $lateDays = null,
    ): Salary {
        if ($salary->isPaid()) {
            throw new RuntimeException(
                'A paid salary cannot be recalculated.',
            );
        }

        return DB::transaction(function () use (
            $salary,
            $upad,
            $paidLoan,
            $adjustment,
            $adjustmentReason,
            $lateDays,
        ): Salary {
            $salary->loadMissing('user');

            $attributes = $this->salaryCalculationAttributes(
                user: $salary->user,
                periodStart: $salary->salary_period_start,
                periodEnd: $salary->salary_period_end,
                lateDays: $lateDays ?? (int) $salary->late_days,
                upad: $upad,
                paidLoan: $paidLoan,
                adjustment: $adjustment,
            );

            $salary->update([
                ...$attributes,
                'adjustment_reason' => $adjustmentReason,
            ]);

            return $salary->fresh();
        });
    }

    /**
     * Mark salary as paid.
     */
    public function markAsPaid(
        Salary $salary,
        ?Carbon $paidAt = null,
    ): Salary {
        if ($salary->isPaid()) {
            throw new RuntimeException(
                'Salary has already been paid.',
            );
        }

        return DB::transaction(function () use (
            $salary,
            $paidAt,
        ): Salary {
            $salary->update([
                'status' => SalaryStatus::PAID,
                'paid_at' => $paidAt ?? now(),
            ]);

            return $salary->fresh();
        });
    }

    /**
     * Preview salary calculations for a user and period.
     *
     * Returns the automatically calculated late days.
     * The client can edit that value before submitting the form.
     *
     * @return array<string, int|string>
     */
    public function preview(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): array {
        $attendance = $this->calculateAttendance(
            $user,
            $periodStart,
            $periodEnd,
        );

        $attendanceDays = $attendance['attendance_days'];
        $lateDays = $attendance['calculated_late_days'];

        // A preview must remain valid even if undertime exceeds
        // the number of attendance days.
        $lateDays = min($attendanceDays, $lateDays);
        $workingDays = $attendanceDays - $lateDays;

        $baseSalary = (float) $user->salary;
        $incentiveRate = (float) $user->incentive_per_day;
        $deductionRate = (float) $user->deduction_per_day;

        $incentive = $this->calculateIncentive(
            $workingDays,
            $incentiveRate,
        );

        $deduction = $this->calculateDeduction(
            $workingDays,
            $deductionRate,
        );

        $finalPayable = $this->calculateFinalPayable(
            baseSalary: $baseSalary,
            workingDays: $workingDays,
            incentive: $incentive,
            adjustment: 0,
            upad: 0,
            paidLoan: 0,
            deduction: $deduction,
        );

        return [
            'base_salary' => number_format($baseSalary, 2, '.', ''),

            'attendance_days' => $attendanceDays,
            'total_undertime_seconds' => $attendance['total_undertime_seconds'],
            'calculated_late_days' => $lateDays,
            'late_days' => $lateDays,
            'working_days' => $workingDays,

            'incentive_rate' => number_format($incentiveRate, 2, '.', ''),
            'incentive' => number_format($incentive, 2, '.', ''),

            'deduction_rate' => number_format($deductionRate, 2, '.', ''),
            'deduction' => number_format($deduction, 2, '.', ''),

            'final_payable' => number_format($finalPayable, 2, '.', ''),
        ];
    }
}
