<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceImportException;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\Attendances\AttendanceImportRequest;
use App\Http\Requests\Attendances\AttendanceRequest;
use App\Http\Requests\Attendances\IndexAttendanceRequest;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Resources\AttendanceResource;
use App\Imports\AttendanceImport;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Throwable;

class AttendanceController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Attendance';

    private const string INDEX_ROUTE = 'attendances.index';

    public function __construct(private readonly AttendanceService $attendanceService) {}

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function index(
        IndexAttendanceRequest $request,
    ): Response {
        Gate::authorize(
            'viewAny',
            Attendance::class,
        );

        $filters = $request->validated();

        $attendances = Attendance::query()
            ->with([
                'user:id,name,employee_code',
                'breaks',
            ])
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Attendances/Index', [
            'attendances' => AttendanceResource::collection($attendances),
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
    ): RedirectResponse {
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
        Attendance $attendance,
    ): RedirectResponse {
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
    ): RedirectResponse {
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
    ): RedirectResponse {
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
    ): RedirectResponse {
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

    public function sync(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sync_type' => [
                'required',
                'string',
                'in:in_out,punches',
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'from' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],
        ]);

        $fromDate = Carbon::createFromFormat(
            '!Y-m-d',
            $validated['from'],
        );

        $toDate = Carbon::createFromFormat(
            '!Y-m-d',
            $validated['to'],
        );

        $command = match ($validated['sync_type']) {
            'in_out' => 'etimeoffice:sync',
            'punches' => 'etimeoffice:sync-punches',
        };

        /*
         * Each command expects a different date format:
         *
         * etimeoffice:sync
         *     d/m/Y
         *
         * etimeoffice:sync-punches
         *     d/m/Y_H:i
         */
        $arguments = match ($validated['sync_type']) {
            'in_out' => [
                '--from' => $fromDate->format('d/m/Y'),
                '--to' => $toDate->format('d/m/Y'),
            ],

            'punches' => [
                '--from' => $fromDate->startOfDay()->format('d/m/Y_H:i'),
                '--to' => $toDate->endOfDay()->format('d/m/Y_H:i'),
            ],
        };

        $arguments['--empcode'] = 'ALL';

        if (! empty($validated['user_id'])) {
            $user = User::query()->findOrFail(
                $validated['user_id'],
            );

            if (blank($user->employee_code)) {
                throw ValidationException::withMessages([
                    'user_id' => 'The selected employee has no eTimeOffice employee code.',
                ]);
            }

            $arguments['--empcode'] = $user->employee_code;
        }

        /*
         * Run synchronously so the command output and exit code
         * are available for the response.
         */
        @set_time_limit(0);

        try {
            $exitCode = Artisan::call(
                $command,
                $arguments,
            );

            $output = trim(Artisan::output());

            if ($exitCode !== Command::SUCCESS) {
                logger()->error('eTimeOffice attendance sync failed.', [
                    'command' => $command,
                    'arguments' => $arguments,
                    'output' => $output,
                ]);

                return back()->with(
                    'error',
                    'Attendance synchronization failed. Please check the application logs.',
                );
            }

            return back()->with(
                'success',
                $output !== ''
                    ? $output
                    : 'Attendance synchronization completed successfully.',
            );
        } catch (Throwable $exception) {
            report($exception);

            logger()->error('Unable to run eTimeOffice attendance sync.', [
                'command' => $command,
                'arguments' => $arguments,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with(
                'error',
                'Unable to sync attendance. Please try again or check the application logs.',
            );
        }
    }
}
