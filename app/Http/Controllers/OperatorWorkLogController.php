<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Enums\UserShift;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\OperatorWorkLogs\IndexOperatorWorkLogRequest;
use App\Http\Requests\OperatorWorkLogs\OperatorWorkLogRequest;
use App\Http\Resources\OperatorWorkLogResource;
use App\Models\Machine;
use App\Models\OperatorWorkLog;
use App\Models\Part;
use App\Models\PartSetup;
use App\Models\Setup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

        $filters = $request->validated();

        $operatorWorkLogs = $this->applyIndexFilters(
            OperatorWorkLog::query()->with([
                'user',
                'machine',
                'part',
                'setup',
                'partSetup',
            ]),
            $filters,
            [],
        )
            ->when(
                $filters['work_date'] ?? null,
                fn (Builder $query, string $workDate): Builder => $query
                    ->whereDate('work_date', $workDate),
            )
            ->when(
                $filters['shift'] ?? null,
                fn (Builder $query, string $shift): Builder => $query
                    ->where('shift', $shift),
            )
            ->when(
                $filters['user_id'] ?? null,
                fn (Builder $query, int $userId): Builder => $query
                    ->where('user_id', $userId),
            )
            ->when(
                $filters['machine_id'] ?? null,
                fn (Builder $query, int $machineId): Builder => $query
                    ->where('machine_id', $machineId),
            )
            ->when(
                $filters['part_id'] ?? null,
                fn (Builder $query, int $partId): Builder => $query
                    ->where('part_id', $partId),
            )
            ->when(
                $filters['setup_id'] ?? null,
                fn (Builder $query, int $setupId): Builder => $query
                    ->where('setup_id', $setupId),
            )
            ->orderBy(
                $filters['sort'] ?? 'work_date',
                $filters['direction'] ?? 'desc',
            )
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('WorkLogs/Operator/Index', [
            'operatorWorkLogs' => OperatorWorkLogResource::collection(
                $operatorWorkLogs,
            ),

            'filters' => [
                'search' => $filters['search'] ?? '',
                'workDate' => $filters['work_date'] ?? '',
                'shift' => $filters['shift'] ?? 'all',
                'userId' => $filters['user_id'] ?? 'all',
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

    public function create(): Response
    {
        Gate::authorize('create', OperatorWorkLog::class);

        return Inertia::render('WorkLogs/Operator/Create', [
            ...$this->formOptions(includeLastReports: true),
        ]);
    }

    public function store(
        OperatorWorkLogRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', OperatorWorkLog::class);

        $data = $request->validated();

        $data['production_seconds'] =
            $data['quantity'] * $data['actual_cycle_time'];

        OperatorWorkLog::create($data);

        return $this->successResponse('created');
    }

    public function edit(OperatorWorkLog $operator): Response
    {
        Gate::authorize('view', $operator);

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

    public function update(
        OperatorWorkLogRequest $request,
        OperatorWorkLog $operator,
    ): RedirectResponse {
        Gate::authorize('update', $operator);

        $data = $request->validated();

        $data['production_seconds'] =
            $data['quantity'] * $data['actual_cycle_time'];

        $operator->update($data);

        return $this->successResponse('updated');
    }

    public function destroy(
        OperatorWorkLog $operator,
    ): RedirectResponse {
        Gate::authorize('delete', $operator);

        $operator->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', OperatorWorkLog::class);

        OperatorWorkLog::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
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

        $canSelectOperator = in_array($role, [
            UserRole::SUPER_ADMIN->value,
            UserRole::SUPERVISOR->value,
        ], true);

        $options = [
            'operators' => User::query()
                ->where('role', UserRole::OPERATOR->value)
                ->where('status', Status::ACTIVE->value)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => "{$user->name} ({$user->employee_code})",
                ])
                ->values(),

            'machines' => Machine::query()
                ->where('status', Status::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Machine $machine): array => [
                    'value' => $machine->id,
                    'label' => $machine->name,
                ])
                ->values(),

            'parts' => Part::query()
                ->where('status', Status::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name', 'reference_name'])
                ->map(fn (Part $part): array => [
                    'value' => $part->id,
                    'label' => $part->reference_name
                        ? "{$part->reference_name} - {$part->name}"
                        : $part->name,
                ])
                ->values(),

            'setups' => Setup::query()
                ->where('status', Status::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Setup $setup): array => [
                    'value' => $setup->id,
                    'label' => $setup->name,
                ])
                ->values(),

            'shifts' => collect(UserShift::cases())
                ->map(fn (UserShift $shift): array => [
                    'value' => $shift->value,
                    'label' => str($shift->value)
                        ->headline()
                        ->toString(),
                ])
                ->values(),

            'partSetups' => PartSetup::query()
                ->get([
                    'id',
                    'part_id',
                    'setup_id',
                    'machine_cycle_time_seconds',
                    'actual_cycle_time_seconds',
                ]),
        ];

        if (! $includeLastReports) {
            return $options;
        }

        $operatorIds = User::query()
            ->where('role', UserRole::OPERATOR->value)
            ->where('status', Status::ACTIVE->value)
            ->pluck('id');

        // Include the logged-in user in case their account is inactive.
        if ($role === UserRole::OPERATOR->value) {
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
}
