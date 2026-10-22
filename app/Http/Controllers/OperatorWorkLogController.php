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
use App\Models\Attendance;
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

    public function index(IndexOperatorWorkLogRequest $request): Response
    {
        Gate::authorize('viewAny', OperatorWorkLog::class);

        $user = $request->user();
        $filters = $request->validated();
        $isOperator = $user->role === UserRole::OPERATOR;

        $query = $this->withDailyProductionMetrics(
            OperatorWorkLog::query()
                ->select('operator_work_logs.*')
                ->with(['user', 'machine', 'part', 'setup']),
        );

        $operatorWorkLogs = $query
            // Operators can only see their own work logs.
            ->when(
                $isOperator,
                fn (Builder $query): Builder => $query->forUser($user->id),
            )
            // Apply all normal filters through HasFilter.
            ->when(
                ! $isOperator,
                fn (Builder $query): Builder => $query->filterQuery($filters),
            )
            // Operators also use HasFilter, but their user_id must not
            // override the mandatory user restriction above.
            ->when(
                $isOperator,
                fn (Builder $query): Builder => $query->filterQuery(
                    array_diff_key($filters, ['user_id' => true]),
                ),
            )
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

    private function withDailyProductionMetrics(Builder $query): Builder
    {
        return $query->addSelect([
            'daily_production_seconds' => OperatorWorkLog::query()
                ->from('operator_work_logs as daily_logs')
                ->selectRaw('COALESCE(SUM(daily_logs.production_seconds), 0)')
                ->whereColumn(
                    'daily_logs.user_id',
                    'operator_work_logs.user_id',
                )
                ->whereRaw(
                    'DATE(daily_logs.work_date) = DATE(operator_work_logs.work_date)',
                ),

            'effective_working_seconds' => Attendance::query()
                ->select('effective_working_seconds')
                ->whereColumn(
                    'attendances.user_id',
                    'operator_work_logs.user_id',
                )
                ->whereRaw(
                    'DATE(attendances.attendance_date) = DATE(operator_work_logs.work_date)',
                )
                ->limit(1),
        ]);
    }

    /**
     * Shared dropdown options for index, create and edit.
     *
     * @return array<string, mixed>
     */
    private function formOptions(bool $includeLastReports = false): array
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
            'partSetups' => PartSetup::query()->get([
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

        $operatorIds = User::query()
            ->where('role', UserRole::OPERATOR->value)
            ->where('status', Status::ACTIVE->value)
            ->pluck('id');

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
            ->mapWithKeys(fn (OperatorWorkLog $log): array => [
                (string) $log->user_id => [
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

    public function create(): Response
    {
        Gate::authorize('create', OperatorWorkLog::class);

        return Inertia::render('WorkLogs/Operator/Create', [
            ...$this->formOptions(includeLastReports: true),
        ]);
    }

    public function store(OperatorWorkLogRequest $request): RedirectResponse
    {
        Gate::authorize('create', OperatorWorkLog::class);

        $data = $request->validated();
        $user = $request->user();

        if ($user->role === UserRole::OPERATOR) {
            $data['user_id'] = $user->id;
        }

        $partSetup = $this->resolvePartSetup(
            partSetupId: $data['part_setup_id'],
            partId: $data['part_id'],
            setupId: $data['setup_id'],
        );

        $data['machine_cycle_time'] = $partSetup->machine_cycle_time_seconds;
        $data['production_seconds'] = $data['quantity'] * $data['actual_cycle_time'];

        OperatorWorkLog::create($data);

        return $this->successResponse('created');
    }

    public function edit(OperatorWorkLog $operator): Response
    {
        Gate::authorize('update', $operator);

        $operator = $this->withDailyProductionMetrics(
            OperatorWorkLog::query()
                ->select('operator_work_logs.*')
                ->with(['user', 'machine', 'part', 'setup', 'partSetup']),
        )
            ->whereKey($operator->getKey())
            ->firstOrFail();

        return Inertia::render('WorkLogs/Operator/Edit', [
            'operatorWorkLog' => new OperatorWorkLogResource($operator),
            ...$this->formOptions(includeLastReports: true),
        ]);
    }

    public function update(
        OperatorWorkLogRequest $request,
        OperatorWorkLog $operator,
    ): RedirectResponse {
        Gate::authorize('update', $operator);

        $data = $request->validated();

        $partSetup = $this->resolvePartSetup(
            partSetupId: $data['part_setup_id'],
            partId: $data['part_id'],
            setupId: $data['setup_id'],
        );

        $data['machine_cycle_time'] = $partSetup->machine_cycle_time_seconds;
        $data['production_seconds'] = $data['quantity'] * $data['actual_cycle_time'];

        $operator->update($data);

        return $this->successResponse('updated');
    }

    /**
     * Resolve a part setup belonging to the selected part and setup.
     *
     * @throws ValidationException
     */
    private function resolvePartSetup(
        int $partSetupId,
        int $partId,
        int $setupId,
    ): PartSetup {
        $partSetup = PartSetup::query()
            ->whereKey($partSetupId)
            ->where('part_id', $partId)
            ->where('setup_id', $setupId)
            ->first();

        if (! $partSetup) {
            throw ValidationException::withMessages([
                'part_setup_id' => 'The selected part setup is invalid for the selected part and setup.',
            ]);
        }

        return $partSetup;
    }

    public function destroy(OperatorWorkLog $operator): RedirectResponse
    {
        Gate::authorize('delete', $operator);

        $operator->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        Gate::authorize('deleteAny', OperatorWorkLog::class);

        OperatorWorkLog::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
