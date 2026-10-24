<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Setups\IndexSetupRequest;
use App\Http\Requests\Setups\SetupRequest;
use App\Http\Resources\SetupResource;
use App\Models\Setup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Setup';

    private const string INDEX_ROUTE = 'setups.index';

    public function index(IndexSetupRequest $request): Response
    {
        Gate::authorize('viewAny', Setup::class);

        $filters = $request->validated();

        $setups = Setup::query()
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Setups/Index', [
            'setups' => SetupResource::collection($setups),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'id',
                'direction' => $filters['direction'] ?? 'desc',
            ],
        ]);
    }

    public function store(SetupRequest $request): RedirectResponse
    {
        Gate::authorize('create', Setup::class);

        Setup::create($request->validated());

        return $this->successResponse('created');
    }

    public function create(): Response
    {
        Gate::authorize('create', Setup::class);

        return Inertia::render('Setups/Create', []);
    }

    public function edit(Setup $setup): Response
    {
        Gate::authorize('view', $setup);

        return Inertia::render('Setups/Edit', [
            'setup' => new SetupResource($setup),
        ]);
    }

    public function update(
        SetupRequest $request,
        Setup $setup,
    ): RedirectResponse {
        Gate::authorize('update', $setup);

        $setup->update($request->validated());

        return $this->successResponse('updated');
    }

    public function destroy(Setup $setup): RedirectResponse
    {
        Gate::authorize('delete', $setup);

        $setup->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', Setup::class);

        Setup::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
