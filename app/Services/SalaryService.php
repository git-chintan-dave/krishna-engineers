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
use RuntimeException;

class SalaryService
{
    /**
     * Generate a salary record for a user and period.
     */
    public function generate(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        float $upad = 0,
        float $paidLoan = 0,
        float $adjustment = 0,
        ?string $adjustmentReason = null,
    ): Salary {
        return DB::transaction(function () use (
            $user,
            $periodStart,
            $periodEnd,
            $upad,
            $paidLoan,
            $adjustment,
            $adjustmentReason,
        ): Salary {
            $existing = $user->salaries()
                ->where(
                    'salary_period_start',
                    $periodStart->toDateString(),
                )
                ->where(
                    'salary_period_end',
                    $periodEnd->toDateString(),
                )
                ->first();

            if ($existing) {
                throw new RuntimeException(
                    'Salary for this period has already been generated.',
                );
            }

            $workingDays = $this->getWorkingDays(
                $user,
                $periodStart,
                $periodEnd,
            );

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
                incentive: $incentive,
                adjustment: $adjustment,
                upad: $upad,
                paidLoan: $paidLoan,
                deduction: $deduction,
            );

            try {
                return $user->salaries()->create([
                    'salary_period_start' => $periodStart->toDateString(),
                    'salary_period_end' => $periodEnd->toDateString(),

                    'base_salary' => $baseSalary,
                    'working_days' => $workingDays,

                    'upad' => $upad,
                    'paid_loan' => $paidLoan,

                    'incentive_rate' => $incentiveRate,
                    'incentive' => $incentive,

                    'deduction_rate' => $deductionRate,
                    'deduction' => $deduction,

                    'adjustment' => $adjustment,
                    'adjustment_reason' => $adjustmentReason,

                    'final_payable' => $finalPayable,

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
     * Generate draft salaries for all eligible employees
     * for the previous calendar month.
     *
     * Existing salary records for the period are skipped.
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
    public function generateForAllEmployees(
        ?CarbonInterface $referenceDate = null,
    ): array {
        $referenceDate = $referenceDate
            ? Carbon::instance($referenceDate)
            : now();

        $periodStart = $referenceDate
            ->copy()
            ->subMonthNoOverflow()
            ->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->endOfMonth();

        return DB::transaction(function () use (
            $periodStart,
            $periodEnd,
        ): array {
            /*
            |--------------------------------------------------------------------------
            | Employees
            |--------------------------------------------------------------------------
            |
            | Only active employees are eligible for salary generation.
            |
            */

            $employees = User::query()
                ->where('status', 'active')
                ->get();

            $existingEmployeeIds = Salary::query()
                ->where(
                    'salary_period_start',
                    $periodStart->toDateString(),
                )
                ->where(
                    'salary_period_end',
                    $periodEnd->toDateString(),
                )
                ->pluck('user_id');

            $generated = collect();
            $skipped = 0;

            foreach ($employees as $employee) {
                if ($existingEmployeeIds->contains($employee->id)) {
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
     * Recalculate a draft salary.
     */
    public function recalculate(
        Salary $salary,
        float $upad = 0,
        float $paidLoan = 0,
        float $adjustment = 0,
        ?string $adjustmentReason = null,
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
        ): Salary {
            $salary->loadMissing('user');

            $workingDays = $this->getWorkingDays(
                $salary->user,
                $salary->salary_period_start,
                $salary->salary_period_end,
            );

            $incentive = $this->calculateIncentive(
                $workingDays,
                (float) $salary->incentive_rate,
            );

            $deduction = $this->calculateDeduction(
                $workingDays,
                (float) $salary->deduction_rate,
            );

            $finalPayable = $this->calculateFinalPayable(
                baseSalary: (float) $salary->base_salary,
                incentive: $incentive,
                adjustment: $adjustment,
                upad: $upad,
                paidLoan: $paidLoan,
                deduction: $deduction,
            );

            $salary->update([
                'working_days' => $workingDays,

                'upad' => $upad,
                'paid_loan' => $paidLoan,

                'incentive' => $incentive,
                'deduction' => $deduction,

                'adjustment' => $adjustment,
                'adjustment_reason' => $adjustmentReason,

                'final_payable' => $finalPayable,
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

    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    /**
     * Get the number of working days for a salary period.
     */
    private function getWorkingDays(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): int {
        return $user->attendances()
            ->whereBetween('attendance_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->whereNotNull('start_at')
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Calculation Helpers
    |--------------------------------------------------------------------------
    */

    private function calculateIncentive(
        int $workingDays,
        float $rate,
    ): float {
        if ($workingDays <= 23) {
            return 0;
        }

        return round(
            ($workingDays - 23) * $rate,
            2,
        );
    }

    private function calculateDeduction(
        int $workingDays,
        float $rate,
    ): float {
        if ($workingDays >= 21) {
            return 0;
        }

        return round(
            (21 - $workingDays) * $rate,
            2,
        );
    }

    private function calculateFinalPayable(
        float $baseSalary,
        float $incentive,
        float $adjustment,
        float $upad,
        float $paidLoan,
        float $deduction,
    ): float {
        return round(
            $baseSalary
            + $incentive
            + $adjustment
            - $upad
            - $paidLoan
            - $deduction,
            2,
        );
    }

    /**
     * Preview salary calculation for a user and period.
     */
    public function preview(
        User $user,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): array {
        $workingDays = $this->getWorkingDays(
            $user,
            $periodStart,
            $periodEnd,
        );

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
            incentive: $incentive,
            adjustment: 0,
            upad: 0,
            paidLoan: 0,
            deduction: $deduction,
        );

        return [
            'base_salary' => number_format(
                $baseSalary,
                2,
                '.',
                '',
            ),
            'working_days' => $workingDays,
            'incentive_rate' => number_format(
                $incentiveRate,
                2,
                '.',
                '',
            ),
            'incentive' => number_format(
                $incentive,
                2,
                '.',
                '',
            ),
            'deduction_rate' => number_format(
                $deductionRate,
                2,
                '.',
                '',
            ),
            'deduction' => number_format(
                $deduction,
                2,
                '.',
                '',
            ),
            'final_payable' => number_format(
                $finalPayable,
                2,
                '.',
                '',
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Database Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether the database exception is a unique constraint
     * violation.
     */
    private function isUniqueViolation(
        QueryException $exception,
    ): bool {
        return $exception->getCode() === '23000';
    }
}
