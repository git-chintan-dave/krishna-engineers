<?php

namespace App\Http\Controllers;

use App\Enums\SalaryStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
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
    use HasCrudResponses;

    private const string MODULE_NAME = 'Salary';

    private const string INDEX_ROUTE = 'salaries.index';

    public function __construct(
        private readonly SalaryService $salaryService,
    ) {}

    public function index(IndexSalaryRequest $request): Response
    {
        Gate::authorize('viewAny', Salary::class);

        $filters = $request->validated();

        $salaries = Salary::query()
            ->with('user:id,name,employee_code')
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $now = now()->subMonthNoOverflow();

        $periodStart = $now->copy()->startOfMonth()->toDateString();
        $periodEnd = $now->copy()->endOfMonth()->toDateString();

        $canGenerateLastMonth = ! Salary::query()
            ->whereDate('salary_period_start', $periodStart)
            ->whereDate('salary_period_end', $periodEnd)
            ->exists();

        return Inertia::render('Salaries/Index', [
            'salaries' => SalaryResource::collection($salaries),
            'can_generate_last_month' => $canGenerateLastMonth,
            'filters' => [
                'user_id' => $filters['user_id'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'id',
                'direction' => $filters['direction'] ?? 'desc',
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
            upad: (float) $data['upad'],
            paidLoan: (float) $data['paid_loan'],
            adjustment: (float) $data['adjustment'],
            adjustmentReason: $data['adjustment_reason'] ?? null,
            lateDays: (int) $data['late_days'],
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
        Salary $salary,
    ): RedirectResponse {
        Gate::authorize('update', $salary);

        $data = $request->validated();

        $this->salaryService->recalculate(
            salary: $salary,
            upad: (float) $data['upad'],
            paidLoan: (float) $data['paid_loan'],
            adjustment: (float) $data['adjustment'],
            adjustmentReason: $data['adjustment_reason'] ?? null,
            lateDays: (int) $data['late_days'],
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
    ): RedirectResponse {
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
    ): JsonResponse {
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
        ?int $userId = null,
    ): RedirectResponse {
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
        Salary $salary,
        SalaryService $salaryService,
    ): RedirectResponse {
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
}
