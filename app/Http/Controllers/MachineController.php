<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Machines\IndexMachineRequest;
use App\Http\Requests\Machines\MachineRequest;
use App\Http\Resources\MachineResource;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MachineController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Machine';

    private const string INDEX_ROUTE = 'machines.index';

    public function index(IndexMachineRequest $request): Response
    {
        Gate::authorize('viewAny', Machine::class);

        $filters = $request->validated();

        $machines = $this->applyIndexFilters(
            Machine::query(),
            $filters,
            ['name', 'machine_number'],
        )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where(
                    'status',
                    $status,
                ),
            )
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Machines/Index', [
            'machines' => MachineResource::collection($machines),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'name',
                'direction' => $filters['direction'] ?? 'asc',
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Machine::class);

        return Inertia::render('Machines/Create', []);
    }

    public function store(MachineRequest $request): RedirectResponse
    {
        Gate::authorize('create', Machine::class);

        Machine::create($request->validated());

        return $this->successResponse('created');
    }

    public function edit(Machine $machine): Response
    {
        Gate::authorize('view', $machine);

        return Inertia::render('Machines/Edit', [
            'machine' => new MachineResource($machine),
        ]);
    }

    public function update(
        MachineRequest $request,
        Machine $machine,
    ): RedirectResponse {
        Gate::authorize('update', $machine);

        $machine->update($request->validated());

        return $this->successResponse('updated');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        Gate::authorize('delete', $machine);

        $machine->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', Machine::class);

        Machine::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
