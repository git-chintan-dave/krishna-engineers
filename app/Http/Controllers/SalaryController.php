<?php

namespace App\Http\Controllers;

use App\Enums\SalaryStatus;
use App\Enums\UserRole;
use App\Exports\Salary\SalaryAttendanceSheet;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Salaries\IndexSalaryRequest;
use App\Http\Requests\Salaries\SalaryPreviewRequest;
use App\Http\Requests\Salaries\SalaryRequest;
use App\Http\Resources\SalaryResource;
use App\Models\Salary;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\OperatorProductionEfficiencyService;
use App\Services\SalaryService;
use App\Support\SalaryReportFileName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;
use ZipArchive;

class SalaryController extends Controller
{
    use HasCrudResponses, HasListingFilters;

    private const string MODULE_NAME = 'Salary';

    private const string INDEX_ROUTE = 'salaries.index';

    public function __construct(
        private readonly SalaryService $salaryService,
    ) {}

    public function index(IndexSalaryRequest $request): Response
    {
        Gate::authorize('viewAny', Salary::class);

        $filters = $request->validated();

        $lastMonth = now()->subMonthNoOverflow();

        $periodStart = $lastMonth->copy()->startOfMonth()->toDateString();
        $periodEnd = $lastMonth->copy()->endOfMonth()->toDateString();

        $canGenerateLastMonth = ! Salary::query()
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
                fn () => $this->getStatistics($filters),
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
            incentive: (float) $data['incentive'],
            deduction: (float) $data['deduction'],
            bonus: (float) $data['bonus'],
        );

        return $this->successResponse('updated');
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

        Salary::query()
            ->whereIn('id', $request->input('ids', []))
            ->where('status', SalaryStatus::DRAFT->value)
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    public function markAsPaid(Salary $salary): RedirectResponse
    {
        Gate::authorize('markAsPaid', $salary);

        $this->salaryService->markAsPaid($salary);

        return $this->successResponse('paid');
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

    public function generateSalary(?int $userId = null): RedirectResponse
    {
        try {
            $result = $this->salaryService->generateForEmployees(
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
        } catch (RuntimeException $exception) {
            return $this->errorResponse(
                'generated',
                $exception->getMessage(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse('generated');
        }
    }

    public function regenerate(Salary $salary): RedirectResponse
    {
        try {
            $this->salaryService->regenerate($salary);

            return $this->successResponse(
                'generated',
                'Salary regenerated successfully.',
            );
        } catch (RuntimeException $exception) {
            return $this->errorResponse(
                'generated',
                $exception->getMessage(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse('generated');
        }
    }

    public function export(
        Salary $salary,
        AttendanceService $attendanceService,
    ) {
        Gate::authorize('view', $salary);

        $salary->loadMissing('user');

        $history = $attendanceService->history(
            $salary->user,
            $salary->salary_period_start,
            $salary->salary_period_end,
        );

        return Excel::download(
            new SalaryAttendanceSheet($salary, $history),
            SalaryReportFileName::make($salary),
            \Maatwebsite\Excel\Excel::XLSX,
        );
    }

    public function exportZip(
        Request $request,
        AttendanceService $attendanceService,
    ) {
        Gate::authorize('viewAny', Salary::class);

        set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $validated = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'start_date' => [
                'nullable',
                'required_with:end_date',
                'date',
            ],
            'end_date' => [
                'nullable',
                'required_with:start_date',
                'date',
                'after_or_equal:start_date',
            ],
        ]);

        if (! empty($validated['start_date'])) {
            $periodStart = Carbon::parse($validated['start_date'])->startOfDay();
            $periodEnd = Carbon::parse($validated['end_date'])->endOfDay();
        } else {
            $lastMonth = now()->subMonthNoOverflow();

            $periodStart = $lastMonth->copy()->startOfMonth();
            $periodEnd = $lastMonth->copy()->endOfMonth();
        }

        $salaryQuery = Salary::query()
            ->with('user:id,name,employee_code')
            ->when(
                $validated['user_id'] ?? null,
                fn ($query, $userId) => $query->where('user_id', $userId),
            )
            ->whereDate(
                'salary_period_start',
                '>=',
                $periodStart->toDateString(),
            )
            ->whereDate(
                'salary_period_end',
                '<=',
                $periodEnd->toDateString(),
            )
            ->orderBy('user_id')
            ->orderBy('id');

        if (! $salaryQuery->exists()) {
            abort(404, 'No salary records found for the selected period.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(
                'The PHP ZIP extension is required to export salary reports.',
            );
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'salary-attendance-');

        if ($zipPath === false) {
            throw new RuntimeException('Unable to create the ZIP file.');
        }

        $temporaryDirectory = 'salary-exports/'.Str::uuid();

        $disk = Storage::disk('local');
        $disk->makeDirectory($temporaryDirectory);

        $zip = new ZipArchive;
        $zipOpened = false;

        try {
            $result = $zip->open(
                $zipPath,
                ZipArchive::CREATE | ZipArchive::OVERWRITE,
            );

            if ($result !== true) {
                throw new RuntimeException('Unable to open the ZIP file.');
            }

            $zipOpened = true;

            $salaryQuery->chunk(20, function ($salaries) use (
                $attendanceService,
                $disk,
                $temporaryDirectory,
                $zip,
            ) {
                foreach ($salaries as $salary) {
                    Gate::authorize('view', $salary);

                    $history = $attendanceService->history(
                        $salary->user,
                        $salary->salary_period_start,
                        $salary->salary_period_end,
                    );

                    $fileName = SalaryReportFileName::make($salary);
                    $relativePath = "{$temporaryDirectory}/{$fileName}";

                    $stored = Excel::store(
                        new SalaryAttendanceSheet($salary, $history),
                        $relativePath,
                        'local',
                        \Maatwebsite\Excel\Excel::XLSX,
                    );

                    if (! $stored) {
                        throw new RuntimeException(
                            "Unable to generate {$fileName}.",
                        );
                    }

                    $excelPath = $disk->path($relativePath);

                    if (! $zip->addFile($excelPath, $fileName)) {
                        throw new RuntimeException(
                            "Unable to add {$fileName} to the ZIP file.",
                        );
                    }

                    unset($history, $excelPath);

                    gc_collect_cycles();
                }
            });

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the ZIP file.');
            }

            $zipOpened = false;

            $disk->deleteDirectory($temporaryDirectory);

            $downloadName = sprintf(
                'salary-attendance-reports-%s-to-%s.zip',
                $periodStart->format('d-M-Y'),
                $periodEnd->format('d-M-Y'),
            );

            return response()
                ->download($zipPath, $downloadName)
                ->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            if ($zipOpened) {
                $zip->close();
            }

            $disk->deleteDirectory($temporaryDirectory);

            if (is_file($zipPath)) {
                @unlink($zipPath);
            }

            throw $exception;
        }
    }

    private function getStatistics(array $filters): array
    {
        unset($filters['status']);

        $stats = Salary::query()
            ->filterQuery($filters)
            ->reorder()
            ->select([])
            ->selectRaw(
                'COALESCE(SUM(salaries.final_payable), 0) AS total_payable',
            )
            ->selectRaw(
                'COALESCE(
                    SUM(
                        CASE
                            WHEN salaries.status = ? THEN salaries.final_payable
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_paid',
                [SalaryStatus::PAID->value],
            )
            ->selectRaw(
                'COALESCE(
                    SUM(
                        CASE
                            WHEN salaries.status = ? THEN salaries.final_payable
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_draft',
                [SalaryStatus::DRAFT->value],
            )
            ->selectRaw(
                'COALESCE(SUM(salaries.incentive), 0) AS total_incentive',
            )
            ->selectRaw(
                'COALESCE(SUM(salaries.deduction), 0) AS total_deduction',
            )
            ->selectRaw(
                'COALESCE(SUM(salaries.bonus), 0) AS total_bonus',
            )
            ->selectRaw(
                'COALESCE(SUM(salaries.adjustment), 0) AS total_adjustments',
            )
            ->selectRaw(
                'COALESCE(SUM(salaries.upad), 0) AS total_upad',
            )
            ->selectRaw(
                'COALESCE(SUM(salaries.paid_loan), 0) AS total_paid_loan',
            )
            ->first();

        return [
            'total_payable' => Number::currency($stats->total_payable, in: 'INR'),
            'total_paid' => Number::currency($stats->total_paid, in: 'INR'),
            'total_draft' => Number::currency($stats->total_draft, in: 'INR'),
            'total_incentive' => Number::currency($stats->total_incentive, in: 'INR'),
            'total_deduction' => Number::currency($stats->total_deduction, in: 'INR'),
            'total_bonus' => Number::currency($stats->total_bonus, in: 'INR'),
            'total_adjustments' => Number::currency($stats->total_adjustments, in: 'INR'),
            'total_upad' => Number::currency($stats->total_upad, in: 'INR'),
            'total_paid_loan' => Number::currency($stats->total_paid_loan, in: 'INR'),
        ];
    }
}
