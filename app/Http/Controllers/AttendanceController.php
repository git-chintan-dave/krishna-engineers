<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceImportException;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class AttendanceController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Attendance';

    private const string INDEX_ROUTE = 'attendances.index';

    public function __construct(
        private readonly AttendanceService $attendanceService,
    )
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function index(
        IndexAttendanceRequest $request,
    ): Response
    {
        Gate::authorize(
            'viewAny',
            Attendance::class,
        );

        $filters = $request->validated();

        $query = Attendance::query()->with([
            'user:id,name,employee_code',
            'breaks',
        ]);

        $user = auth()->user();

        if (in_array($user->role?->value ?? $user->role, ['operator', 'bandsaw'], true)) {
            $query->where('user_id', $user->id);
        }

        $attendances = $this->applyIndexFilters(
            $query,
            $filters,
        )
            ->when($filters['user_id'] ?? null,
                fn(Builder $query, int $userId): Builder => $query->where('user_id', $userId),
            )
            ->when($filters['start_date'] ?? null,
                fn(Builder $query, string $date): Builder => $query->whereDate('attendance_date', '>=', $date),
            )
            ->when($filters['end_date'] ?? null,
                fn(Builder $query, string $date): Builder => $query->whereDate('attendance_date', '<=', $date),
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
                'user_id' => $filters['user_id'] ?? 'all',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'attendance_date',
                'direction' => $filters['direction'] ?? 'desc',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function edit(
        Attendance $attendance,
    ): Response
    {
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
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function store(
        AttendanceRequest $request,
    ): RedirectResponse
    {
        Gate::authorize(
            'create',
            Attendance::class,
        );

        $data = $request->validated();

        $breaks = $data['breaks'] ?? [];

        unset($data['breaks']);

        try {
            $this->attendanceService->create($data, $breaks);
        } catch (RuntimeException $e) {
            return $this->errorResponse(
                'created',
                $e->getMessage(),
            );
        }

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize(
            'create',
            Attendance::class,
        );

        return Inertia::render(
            'Attendances/Create',
            [],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        AttendanceRequest $request,
        Attendance        $attendance,
    ): RedirectResponse
    {
        Gate::authorize(
            'update',
            $attendance,
        );

        $data = $request->validated();

        $breaks = $data['breaks'] ?? [];

        unset($data['breaks']);

        try {
            $this->attendanceService->update(
                $attendance,
                $data,
                $breaks,
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse(
                'updated',
                $e->getMessage(),
            );
        }

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Attendance $attendance,
    ): RedirectResponse
    {
        Gate::authorize(
            'delete',
            $attendance,
        );

        try {
            $this->attendanceService->delete($attendance);
        } catch (RuntimeException $e) {
            return $this->errorResponse(
                'deleted',
                $e->getMessage(),
            );
        }

        return $this->successResponse('deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse
    {
        Gate::authorize(
            'deleteAny',
            Attendance::class,
        );

        try {
            $this->attendanceService->deleteMany(
                $request->validated('ids'),
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse(
                'bulk_deleted',
                $e->getMessage(),
            );
        }

        return $this->successResponse('bulk_deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */

    public function importView(): Response
    {
        Gate::authorize(
            'import',
            Attendance::class,
        );

        return Inertia::render(
            'Attendances/Import',
            [],
        );
    }

    public function import(
        AttendanceImportRequest $request,
    ): RedirectResponse
    {
        Gate::authorize(
            'import',
            Attendance::class,
        );

        try {
            Excel::import(
                new AttendanceImport($this->attendanceService),
                $request->file('file'),
            );
        } catch (AttendanceImportException $e) {
            return $this->errorResponse(
                'imported',
                $e->getMessage(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                'imported',
                'Unable to import the attendance report. Please check the file and try again.',
            );
        }

        return $this->successResponse('imported');
    }
}
