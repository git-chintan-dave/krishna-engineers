<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Sections\IndexSectionRequest;
use App\Http\Requests\Sections\SectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Section';

    private const string INDEX_ROUTE = 'sections.index';

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        IndexSectionRequest $request,
    ): Response {
        Gate::authorize('viewAny', Section::class);

        $filters = $request->validated();

        $sections = $this->applyIndexFilters(
            Section::query(),
            $filters,
            [
                'name',
                'status',
            ],
        )
            ->when(
                $filters['status'] ?? null,
                fn (
                    Builder $query,
                    string $status,
                ): Builder => $query->where(
                    'status',
                    $status,
                ),
            )
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Sections/Index', [
            'sections' => SectionResource::collection(
                $sections,
            ),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'name',
                'direction' => $filters['direction'] ?? 'asc',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize('create', Section::class);

        return Inertia::render('Sections/Create', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        SectionRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', Section::class);

        Section::create($request->validated());

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Section $section): Response
    {
        Gate::authorize('view', $section);

        return Inertia::render('Sections/Edit', [
            'section' => new SectionResource($section),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        SectionRequest $request,
        Section $section,
    ): RedirectResponse {
        Gate::authorize('update', $section);

        $section->update($request->validated());

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(Section $section): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $section->delete();

        return $this->successResponse('deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Destroy
    |--------------------------------------------------------------------------
    */

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', Section::class);

        Section::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
