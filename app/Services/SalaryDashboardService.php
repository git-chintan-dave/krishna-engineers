<?php

namespace App\Services;

use App\Enums\SalaryStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaryDashboardService
{
    /**
     * Return the latest two salary periods with their summaries
     * and employee payment details.
     */
    public function getDashboard(): array
    {
        $paidStatus = SalaryStatus::PAID->value;
        $draftStatus = SalaryStatus::DRAFT->value;

        // Aggregate salary records by their actual stored periods.
        $periods = DB::table('salaries')
            ->selectRaw(
                '
                    salary_period_start,
                    salary_period_end,

                    COUNT(*) as employee_count,

                    COALESCE(SUM(final_payable), 0) as total_payable,

                    COALESCE(
                        SUM(CASE WHEN status = ? THEN final_payable ELSE 0 END),
                        0
                    ) as paid_amount,

                    COALESCE(
                        SUM(CASE WHEN status = ? THEN final_payable ELSE 0 END),
                        0
                    ) as pending_amount,

                    COALESCE(
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END),
                        0
                    ) as paid_employees,

                    COALESCE(
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END),
                        0
                    ) as pending_employees,

                    COALESCE(SUM(base_salary), 0) as base_salary,
                    COALESCE(SUM(incentive), 0) as incentive,
                    COALESCE(SUM(deduction), 0) as deduction,
                    COALESCE(SUM(upad), 0) as upad,
                    COALESCE(SUM(paid_loan), 0) as paid_loan,
                    COALESCE(SUM(adjustment), 0) as adjustment
                ',
                [
                    $paidStatus,
                    $draftStatus,
                    $paidStatus,
                    $draftStatus,
                ]
            )
            ->groupBy('salary_period_start', 'salary_period_end')
            ->orderByDesc('salary_period_start')
            ->orderByDesc('salary_period_end')
            ->limit(2)
            ->get();

        if ($periods->isEmpty()) {
            return [
                'currentPeriod' => null,
                'previousPeriod' => null,
            ];
        }

        // Fetch employees for both periods in one query.
        $employees = $this->getPeriodEmployees($periods);

        $currentPeriod = $periods->get(0);

        $previousPeriod = $periods->get(1);

        return [
            'currentPeriod' => $this->formatPeriod(
                $currentPeriod,
                $employees
            ),

            'previousPeriod' => $previousPeriod
                ? $this->formatPeriod($previousPeriod, $employees)
                : null,
        ];
    }

    /**
     * Fetch employee salary records for the selected periods.
     */
    private function getPeriodEmployees(Collection $periods): Collection
    {
        $query = DB::table('salaries as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->select([
                's.id',
                's.salary_period_start',
                's.salary_period_end',
                'u.name',
                's.final_payable',
                's.status',
                's.paid_at',
            ]);

        $query->where(function ($query) use ($periods) {
            foreach ($periods as $period) {
                $query->orWhere(function ($query) use ($period) {
                    $query
                        ->where(
                            's.salary_period_start',
                            $period->salary_period_start
                        )
                        ->where(
                            's.salary_period_end',
                            $period->salary_period_end
                        );
                });
            }
        });

        return $query
            ->orderBy('u.name')
            ->get()
            ->groupBy(
                fn ($employee) => $this->periodKey(
                    $employee->salary_period_start,
                    $employee->salary_period_end
                )
            );
    }

    /**
     * Format a salary period and its employee records.
     */
    private function formatPeriod(
        object $period,
        Collection $employees
    ): array {
        $totalPayable = (float) $period->total_payable;
        $paidAmount = (float) $period->paid_amount;
        $pendingAmount = (float) $period->pending_amount;

        $paymentPercentage = $totalPayable > 0
            ? min(100, ($paidAmount / $totalPayable) * 100)
            : 0;

        $key = $this->periodKey(
            $period->salary_period_start,
            $period->salary_period_end
        );

        $breakdown = [
            'baseSalary' => (float) $period->base_salary,
            'incentive' => (float) $period->incentive,
            'deduction' => (float) $period->deduction,
            'upad' => (float) $period->upad,
            'paidLoan' => (float) $period->paid_loan,
            'adjustment' => (float) $period->adjustment,
        ];

        return [
            'label' => Carbon::parse($period->salary_period_start)->format('F Y'),

            'startDate' => Carbon::parse($period->salary_period_start)->format('d M Y'),

            'endDate' => Carbon::parse($period->salary_period_end)->format('d M Y'),

            'totalPayable' => $totalPayable,
            'totalPayableFormatted' => $this->formatCurrency($totalPayable),

            'paidAmount' => $paidAmount,
            'paidAmountFormatted' => $this->formatCurrency($paidAmount),

            'pendingAmount' => $pendingAmount,
            'pendingAmountFormatted' => $this->formatCurrency($pendingAmount),

            'employeeCount' => (int) $period->employee_count,
            'paidEmployees' => (int) $period->paid_employees,
            'pendingEmployees' => (int) $period->pending_employees,

            'paymentPercentage' => round($paymentPercentage, 1),
            'paymentPercentageFormatted' => number_format($paymentPercentage, 1) . '%',

            'breakdown' => [
                'baseSalary' => $breakdown['baseSalary'],
                'baseSalaryFormatted' => $this->formatCurrency($breakdown['baseSalary']),

                'incentive' => $breakdown['incentive'],
                'incentiveFormatted' => $this->formatCurrency($breakdown['incentive']),

                'deduction' => $breakdown['deduction'],
                'deductionFormatted' => $this->formatCurrency($breakdown['deduction']),

                'upad' => $breakdown['upad'],
                'upadFormatted' => $this->formatCurrency($breakdown['upad']),

                'paidLoan' => $breakdown['paidLoan'],
                'paidLoanFormatted' => $this->formatCurrency($breakdown['paidLoan']),

                'adjustment' => $breakdown['adjustment'],
                'adjustmentFormatted' => $this->formatCurrency($breakdown['adjustment']),
            ],

            'employees' => $employees
                ->get($key, collect())
                ->map(fn ($employee) => [
                    'id' => (int) $employee->id,
                    'name' => $employee->name,
                    'amount' => (float) $employee->final_payable,
                    'amountFormatted' => $this->formatCurrency(
                        $employee->final_payable
                    ),
                    'status' => $employee->status,
                    'paidAt' => $employee->paid_at
                        ? Carbon::parse($employee->paid_at)->format('d M Y')
                        : null,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Create a consistent key for matching period records.
     */
    private function periodKey(string $startDate, string $endDate): string
    {
        return $startDate . '|' . $endDate;
    }

    /**
     * Format currency using Indian number grouping.
     */
    private function formatCurrency(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        $formatted = number_format(abs($amount), 0, '.', '');

        if (strlen($formatted) > 3) {
            $lastThree = substr($formatted, -3);
            $remaining = substr($formatted, 0, -3);

            $remaining = preg_replace(
                '/\B(?=(\d{2})+(?!\d))/',
                ',',
                $remaining
            );

            $formatted = $remaining . ',' . $lastThree;
        }

        return ($amount < 0 ? '-₹' : '₹') . $formatted;
    }
}
