<?php

namespace App\Http\Controllers;

use App\Enums\SalaryStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Salaries\IndexSalaryRequest;
use App\Http\Requests\Salaries\SalaryPreviewRequest;
use App\Http\Requests\Salaries\SalaryRequest;
use App\Http\Resources\SalaryResource;
use App\Models\Salary;
use App\Models\User;
use App\Services\OperatorProductionEfficiencyService;
use App\Services\SalaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class SalaryController extends Controller
{
    use HasCrudResponses, HasListingFilters;

    private const string MODULE_NAME = 'Salary';

    private const string INDEX_ROUTE = 'salaries.index';

    public function __construct(
        private readonly SalaryService $salaryService,
    )
    {
    }

    public function index(IndexSalaryRequest $request): Response
    {
        Gate::authorize('viewAny', Salary::class);

        $filters = $request->validated();

        $now = now()->subMonthNoOverflow();

        $periodStart = $now->copy()->startOfMonth()->toDateString();
        $periodEnd = $now->copy()->endOfMonth()->toDateString();

        $canGenerateLastMonth = !Salary::query()
            ->whereDate('salary_period_start', $periodStart)
            ->whereDate('salary_period_end', $periodEnd)
            ->exists();

        return Inertia::render('Salaries/Index', [
            'salaries' => Inertia::defer(function () use ($filters) {
                $salaries = Salary::query()
                    ->with('user:id,name,employee_code')
                    ->filterQuery($filters)
                    ->paginate($filters['per_page'] ?? 10)
                    ->withQueryString();

                return SalaryResource::collection($salaries);
            }),

            'statistics' => Inertia::defer(
                fn () => $this->getStatistics(),
            ),

            'hasAnyRecords' => Salary::query()->exists(),

            'can_generate_last_month' => $canGenerateLastMonth,

            'filters' => [
                ...$this->listingFilters($filters),
                'user_id' => $filters['user_id'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Salary::class);

        return Inertia::render('Salaries/Create', []);
    }

    public function store(SalaryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Salary::class);

        $data = $request->validated();

        $user = User::query()->findOrFail($data['user_id']);

        $this->salaryService->generate(
            user: $user,
            periodStart: Carbon::parse($data['salary_period_start']),
            periodEnd: Carbon::parse($data['salary_period_end']),
            upad: (float)$data['upad'],
            paidLoan: (float)$data['paid_loan'],
            adjustment: (float)$data['adjustment'],
            adjustmentReason: $data['adjustment_reason'] ?? null,
            lateDays: (int)$data['late_days'],
        );

        return $this->successResponse('created');
    }

    public function show(Salary $salary): Response
    {
        Gate::authorize('view', $salary);

        $salary->load('user:id,name,employee_code,role');

        $isOperator = $salary->user->role === UserRole::OPERATOR;

        $productionEfficiency = $isOperator
            ? app(OperatorProductionEfficiencyService::class)->calculateForPeriod(
                userId: $salary->user_id,
                startDate: $salary->salary_period_start,
                endDate: $salary->salary_period_end,
            )
            : null;

        return Inertia::render('Salaries/Show', [
            'salary' => new SalaryResource($salary),
            'show_production_efficiency' => $isOperator,
            'production_efficiency' => $productionEfficiency,
        ]);
    }

    public function edit(Salary $salary): Response
    {
        Gate::authorize('update', $salary);

        $salary->load('user:id,name,employee_code');

        return Inertia::render('Salaries/Edit', [
            'salary' => new SalaryResource($salary),
        ]);
    }

    public function update(
        SalaryRequest $request,
        Salary        $salary,
    ): RedirectResponse
    {
        Gate::authorize('update', $salary);

        $data = $request->validated();

        $this->salaryService->recalculate(
            salary: $salary,
            upad: (float)$data['upad'],
            paidLoan: (float)$data['paid_loan'],
            adjustment: (float)$data['adjustment'],
            adjustmentReason: $data['adjustment_reason'] ?? null,
            lateDays: (int)$data['late_days'],
        );

        return $this->successResponse('updated');
    }

    public function markAsPaid(Salary $salary): RedirectResponse
    {
        Gate::authorize('markAsPaid', $salary);

        $this->salaryService->markAsPaid($salary);

        return $this->successResponse('paid');
    }

    public function destroy(Salary $salary): RedirectResponse
    {
        Gate::authorize('delete', $salary);

        $salary->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse
    {
        Gate::authorize('deleteAny', Salary::class);

        $ids = $request->input('ids', []);

        Salary::query()
            ->whereIn('id', $ids)
            ->where('status', SalaryStatus::DRAFT->value)
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    public function preview(
        SalaryPreviewRequest $request,
    ): JsonResponse
    {
        Gate::authorize('create', Salary::class);

        $data = $request->validated();

        $user = User::query()->findOrFail($data['user_id']);

        $periodStart = Carbon::parse($data['salary_period_start']);
        $periodEnd = Carbon::parse($data['salary_period_end']);

        $preview = $this->salaryService->preview(
            user: $user,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        $isOperator = $user->role === UserRole::OPERATOR;

        $productionEfficiency = $isOperator
            ? app(OperatorProductionEfficiencyService::class)->calculateForPeriod(
                userId: $user->id,
                startDate: $periodStart,
                endDate: $periodEnd,
            )
            : null;

        return response()->json([
            ...$preview,
            'show_production_efficiency' => $isOperator,
            'production_efficiency' => $productionEfficiency,
        ]);
    }

    public function generateSalary(
        SalaryService $salaryService,
        ?int          $userId = null,
    ): RedirectResponse
    {
        try {
            $result = $salaryService->generateForEmployees(
                userId: $userId,
            );

            return $this->successResponse(
                'generated',
                sprintf(
                    'Last month salary generated successfully. %d generated, %d skipped.',
                    $result['generated'],
                    $result['skipped'],
                ),
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse(
                'generated',
                $e->getMessage(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('generated');
        }
    }

    public function regenerate(
        Salary        $salary,
        SalaryService $salaryService,
    ): RedirectResponse
    {
        try {
            if ($salary->status === 'paid') {
                return $this->errorResponse(
                    'generated',
                    'Paid salaries cannot be regenerated.',
                );
            }

            $salaryService->regenerate($salary);

            return $this->successResponse(
                'generated',
                'Salary regenerated successfully.',
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse(
                'generated',
                $e->getMessage(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('generated');
        }
    }


    private function getStatistics(): array
    {
        $stats = Salary::query()
            ->selectRaw('COUNT(*) AS total')

            // Salary amounts
            ->selectRaw('COALESCE(SUM(final_payable), 0) AS total_payable')
            ->selectRaw('COALESCE(AVG(final_payable), 0) AS average_payable')
            ->selectRaw('COALESCE(SUM(base_salary), 0) AS total_base_salary')
            ->selectRaw('COALESCE(SUM(incentive), 0) AS total_incentive')
            ->selectRaw('COALESCE(SUM(deduction), 0) AS total_deduction')
            ->selectRaw('COALESCE(SUM(upad), 0) AS total_upad')
            ->selectRaw('COALESCE(SUM(paid_loan), 0) AS total_paid_loan')
            ->selectRaw('COALESCE(SUM(adjustment), 0) AS total_adjustments')

            // Attendance and punctuality
            ->selectRaw(
                'COALESCE(SUM(attendance_days), 0) AS total_attendance_days',
            )
            ->selectRaw(
                'COALESCE(SUM(working_days), 0) AS total_working_days',
            )
            ->selectRaw(
                'COALESCE(SUM(late_days), 0) AS total_late_days',
            )
            ->selectRaw(
                'COALESCE(SUM(total_undertime_seconds), 0) AS total_undertime_seconds',
            )

            // Confirmed salary statuses
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS draft_count',
                [SalaryStatus::DRAFT->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS paid_count',
                [SalaryStatus::PAID->value],
            )

            // Amount paid
            ->selectRaw(
                'COALESCE(
                SUM(
                    CASE
                        WHEN status = ? THEN final_payable
                        ELSE 0
                    END
                ),
                0
            ) AS total_paid',
                [SalaryStatus::PAID->value],
            )
            ->firstOrFail();

        return [
            'total' => (int) $stats->total,

            'total_payable' => (float) $stats->total_payable,
            'average_payable' => round((float) $stats->average_payable, 2),
            'total_base_salary' => (float) $stats->total_base_salary,
            'total_incentive' => (float) $stats->total_incentive,
            'total_deduction' => (float) $stats->total_deduction,
            'total_upad' => (float) $stats->total_upad,
            'total_paid_loan' => (float) $stats->total_paid_loan,
            'total_adjustments' => (float) $stats->total_adjustments,

            'total_attendance_days' => (int) $stats->total_attendance_days,
            'total_working_days' => (int) $stats->total_working_days,
            'total_late_days' => (int) $stats->total_late_days,
            'total_undertime_seconds' => (int) $stats->total_undertime_seconds,

            'draft_count' => (int) $stats->draft_count,
            'paid_count' => (int) $stats->paid_count,

            'total_paid' => (float) $stats->total_paid,
        ];
    }
}
