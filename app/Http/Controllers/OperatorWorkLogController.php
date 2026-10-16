<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\OperatorWorkLogs\IndexOperatorWorkLogRequest;
use App\Http\Requests\OperatorWorkLogs\OperatorWorkLogRequest;
use App\Http\Resources\OperatorWorkLogResource;
use App\Models\OperatorWorkLog;
use App\Models\PartSetup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OperatorWorkLogController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Operator Work Log';

    private const string INDEX_ROUTE = 'operator.index';

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(IndexOperatorWorkLogRequest $request): Response
    {
        Gate::authorize('viewAny', OperatorWorkLog::class);

        $user = $request->user();
        $filters = $request->validated();

        $isOperator = $user->role === UserRole::OPERATOR;

        $operatorWorkLogs = $this->applyIndexFilters(
            OperatorWorkLog::query()->with([
                'user',
                'machine',
                'part',
                'setup',
            ]),
            $filters,
            [
                'work_date',
                'quantity',
                'rejection_quantity',
                'production_seconds',
            ],
        )
            ->when($isOperator,
                fn(Builder $query): Builder => $query->forUser($user->id),
            )
            ->when(!$isOperator && ($filters['user_id'] ?? null),
                fn(Builder $query): Builder => $query->forUser($filters['user_id']),
            )
            ->when($filters['work_date'] ?? null,
                fn(Builder $query, string $workDate): Builder => $query->forDate($workDate),
            )
            ->when($filters['shift'] ?? null,
                fn(Builder $query, string $shift): Builder => $query->forShift($shift),
            )
            ->when($filters['machine_id'] ?? null,
                fn(Builder $query, int $machineId): Builder => $query->forMachine($machineId),
            )
            ->when($filters['part_id'] ?? null,
                fn(Builder $query, int $partId): Builder => $query->forPart($partId),
            )
            ->when($filters['setup_id'] ?? null,
                fn(Builder $query, int $setupId): Builder => $query->forSetup($setupId),
            )
            ->orderBy($filters['sort'] ?? 'work_date', $filters['direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('WorkLogs/Operator/Index', [
            'operatorWorkLogs' => OperatorWorkLogResource::collection(
                $operatorWorkLogs,
            ),
            'filters' => [
                'workDate' => $filters['work_date'] ?? '',
                'shift' => $filters['shift'] ?? 'all',
                'userId' => $isOperator
                    ? 'all'
                    : ($filters['user_id'] ?? 'all'),
                'machineId' => $filters['machine_id'] ?? 'all',
                'partId' => $filters['part_id'] ?? 'all',
                'setupId' => $filters['setup_id'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'work_date',
                'direction' => $filters['direction'] ?? 'desc',
            ],
            ...$this->formOptions(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    /**
     * Shared dropdown options for index, create and edit.
     *
     * @return array<string, mixed>
     */
    private function formOptions(
        bool $includeLastReports = false,
    ): array
    {
        $currentUser = Auth::user();

        $role = $currentUser->role instanceof UserRole
            ? $currentUser->role->value
            : $currentUser->role;

        $isOperator = $role === UserRole::OPERATOR->value;

        $canSelectOperator = in_array(
            $role,
            [
                UserRole::SUPER_ADMIN->value,
                UserRole::SUPERVISOR->value,
            ],
            true,
        );

        $options = [
            /*
             * Used by the form to determine machine/actual cycle time
             * after Part + Setup selection.
             */
            'partSetups' => PartSetup::query()
                ->get([
                    'id',
                    'part_id',
                    'setup_id',
                    'machine_cycle_time_seconds',
                    'actual_cycle_time_seconds',
                ]),
        ];

        if (!$includeLastReports) {
            return [
                ...$options,
                'currentUserId' => $currentUser->id,
                'canSelectOperator' => $canSelectOperator,
            ];
        }

        /*
         * Last report per operator is used to make creating the next
         * work log faster by pre-filling shift/machine/part/setup.
         */
        $operatorIds = User::query()
            ->where('role', UserRole::OPERATOR->value)
            ->where('status', Status::ACTIVE->value)
            ->pluck('id');

        /*
         * Include the logged-in operator even if their account is
         * currently inactive.
         */
        if ($isOperator) {
            $operatorIds->push($currentUser->id);
        }

        $lastReports = OperatorWorkLog::query()
            ->whereIn('user_id', $operatorIds->unique())
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->get([
                'user_id',
                'shift',
                'machine_id',
                'part_id',
                'setup_id',
            ])
            ->unique('user_id')
            ->mapWithKeys(fn(
                OperatorWorkLog $log
            ): array => [
                (string)$log->user_id => [
                    'shift' => $log->shift,
                    'machine_id' => $log->machine_id,
                    'part_id' => $log->part_id,
                    'setup_id' => $log->setup_id,
                ],
            ])
            ->all();

        return [
            ...$options,
            'currentUserId' => $currentUser->id,
            'canSelectOperator' => $canSelectOperator,
            'lastReports' => $lastReports,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        OperatorWorkLogRequest $request,
    ): RedirectResponse
    {
        Gate::authorize('create', OperatorWorkLog::class);

        $data = $request->validated();

        $user = $request->user();

        /*
         * Operators can only create a log for themselves.
         *
         * Supervisor / Super Admin can select an operator.
         */
        if ($user->role === UserRole::OPERATOR) {
            $data['user_id'] = $user->id;
        }

        /*
         * Verify the selected Part Setup and obtain its cycle-time
         * configuration from the database.
         */
        $partSetup = $this->resolvePartSetup(
            partSetupId: $data['part_setup_id'],
            partId: $data['part_id'],
            setupId: $data['setup_id'],
        );

        /*
         * Machine cycle time is always the historical value configured
         * in Part Setup. Never trust the frontend value.
         */
        $data['machine_cycle_time'] =
            $partSetup->machine_cycle_time_seconds;

        /*
         * actual_cycle_time is intentionally taken from the request.
         *
         * The form initially receives the Part Setup actual cycle time,
         * but an operator/supervisor may adjust it when the real cycle
         * differs.
         */
        $data['production_seconds'] =
            $data['quantity'] * $data['actual_cycle_time'];

        OperatorWorkLog::create($data);

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve and validate the selected Part Setup.
     *
     * A Part Setup must belong to the selected Part and Setup.
     *
     * @throws ValidationException
     */
    private function resolvePartSetup(
        int $partSetupId,
        int $partId,
        int $setupId,
    ): PartSetup
    {
        $partSetup = PartSetup::query()
            ->whereKey($partSetupId)
            ->where('part_id', $partId)
            ->where('setup_id', $setupId)
            ->first();

        if (!$partSetup) {
            throw ValidationException::withMessages([
                'part_setup_id' => 'The selected part setup is invalid for the selected part and setup.',
            ]);
        }

        return $partSetup;
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize('create', OperatorWorkLog::class);

        return Inertia::render('WorkLogs/Operator/Create', [
            ...$this->formOptions(includeLastReports: true),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function edit(OperatorWorkLog $operator): Response
    {
        Gate::authorize('update', $operator);

        $operator->load([
            'user',
            'machine',
            'part',
            'setup',
            'partSetup',
        ]);

        return Inertia::render('WorkLogs/Operator/Edit', [
            'operatorWorkLog' => new OperatorWorkLogResource(
                $operator,
            ),

            ...$this->formOptions(includeLastReports: true),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Destroy
    |--------------------------------------------------------------------------
    */

    public function update(
        OperatorWorkLogRequest $request,
        OperatorWorkLog        $operator,
    ): RedirectResponse
    {
        Gate::authorize('update', $operator);

        $data = $request->validated();

        /*
         * Supervisors and Super Admins can change the operator.
         *
         * Operators cannot reach this action because the policy denies
         * update(), but keeping the assignment here makes the controller
         * explicit and safe.
         */
        $partSetup = $this->resolvePartSetup(
            partSetupId: $data['part_setup_id'],
            partId: $data['part_id'],
            setupId: $data['setup_id'],
        );

        /*
         * Always refresh the machine cycle snapshot from Part Setup
         * when the work log is updated.
         */
        $data['machine_cycle_time'] =
            $partSetup->machine_cycle_time_seconds;

        /*
         * Recalculate production time whenever quantity or actual cycle
         * time changes.
         */
        $data['production_seconds'] =
            $data['quantity'] * $data['actual_cycle_time'];

        $operator->update($data);

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Form Options
    |--------------------------------------------------------------------------
    */

    public function destroy(
        OperatorWorkLog $operator,
    ): RedirectResponse
    {
        Gate::authorize('delete', $operator);

        $operator->delete();

        return $this->successResponse('deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Part Setup
    |--------------------------------------------------------------------------
    */

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse
    {
        Gate::authorize('deleteAny', OperatorWorkLog::class);

        OperatorWorkLog::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
