<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\Attendances\AttendanceImportRequest;
use App\Http\Requests\Attendances\AttendanceRequest;
use App\Http\Requests\Attendances\IndexAttendanceRequest;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Resources\AttendanceResource;
use App\Imports\AttendanceImport;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Attendance';

    private const string INDEX_ROUTE = 'attendances.index';

    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    // Index
    public function index(
        IndexAttendanceRequest $request,
    ): Response {
        Gate::authorize(
            'viewAny',
            Attendance::class,
        );

        $filters = $request->validated();

        $attendances = $this->applyIndexFilters(
            Attendance::query()->with([
                'user:id,name,employee_code',
                'breaks',
            ]),
            $filters,
        )
            ->when(
                $filters['user_id'] ?? null,
                fn (
                    Builder $query,
                    int $userId,
                ): Builder => $query->where(
                    'user_id',
                    $userId,
                ),
            )
            ->when(
                $filters['start_date'] ?? null,
                fn (
                    Builder $query,
                    string $date,
                ): Builder => $query->whereDate(
                    'attendance_date',
                    '>=',
                    $date,
                ),
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (
                    Builder $query,
                    string $date,
                ): Builder => $query->whereDate(
                    'attendance_date',
                    '<=',
                    $date,
                ),
            )
            ->paginate(
                $filters['per_page'] ?? 10,
            )
            ->withQueryString();

        return Inertia::render('Attendances/Index', [
            'attendances' => AttendanceResource::collection(
                $attendances,
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'user_id' => $filters['user_id'] ?? 'all',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort']
                    ?? 'attendance_date',
                'direction' => $filters['direction']
                    ?? 'desc',
            ],
        ],
        );
    }

    public function create(): Response
    {
        Gate::authorize('create', Attendance::class);

        return Inertia::render('Attendances/Create', []);
    }

    public function edit(
        Attendance $attendance,
    ): Response {
        Gate::authorize(
            'view',
            $attendance,
        );

        $attendance->load([
            'user:id,name,employee_code',
            'breaks',
        ]);

        return Inertia::render('Attendances/Edit', [
            'attendance' => new AttendanceResource(
                $attendance,
            )
        ],
        );
    }

    public function store(
        AttendanceRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'create',
            Attendance::class,
        );

        $data = $request->validated();

        $breaks = $data['breaks'] ?? [];

        unset($data['breaks']);

        $attendance = Attendance::create([
            ...$data,
            'total_working_seconds' => 0,
            'break_seconds' => 0,
            'effective_working_seconds' => 0,
        ]);

        $this->syncBreaks(
            $attendance,
            $breaks,
        );

        $this->attendanceService->recalculateTotals(
            $attendance->fresh(),
        );

        return $this->successResponse(
            'created',
        );
    }

    public function update(
        AttendanceRequest $request,
        Attendance $attendance,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $attendance,
        );

        $data = $request->validated();

        $breaks = $data['breaks'] ?? [];

        unset($data['breaks']);

        $attendance->update($data);

        $this->syncBreaks(
            $attendance,
            $breaks,
        );

        $this->attendanceService->recalculateTotals(
            $attendance->fresh(),
        );

        return $this->successResponse(
            'updated',
        );
    }

    public function destroy(
        Attendance $attendance,
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $attendance,
        );

        $attendance->delete();

        return $this->successResponse(
            'deleted',
        );
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'deleteAny',
            Attendance::class,
        );

        Attendance::query()
            ->whereIn(
                'id',
                $request->validated('ids'),
            )
            ->delete();

        return $this->successResponse(
            'bulk_deleted',
        );
    }

    // Import
    public function importView(): Response
    {
        return Inertia::render('Attendances/Import', []);
    }

    public function import(
        AttendanceImportRequest $request,
    ): RedirectResponse {
        try {
            Excel::import(
                new AttendanceImport(),
                $request->file('file'),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'file' =>
                        'Unable to import the attendance report. '.
                        'Please check the file and try again.',
                ]);
        }

        return $this->successResponse(
            'imported',
        );
    }

    // Helpers
    private function syncBreaks(
        Attendance $attendance,
        array $breaks,
    ): void {
        $attendance->breaks()->delete();

        foreach ($breaks as $break) {
            $startAt = Carbon::parse(
                $break['start_at'],
            );

            $endAt = ! empty($break['end_at'])
                ? Carbon::parse(
                    $break['end_at'],
                )
                : null;

            $durationSeconds = $endAt !== null
                ? max(
                    0,
                    (int) $startAt->diffInSeconds(
                        $endAt,
                    ),
                )
                : 0;

            $attendance->breaks()->create([
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration_seconds' => $durationSeconds,
            ]);
        }
    }
}
