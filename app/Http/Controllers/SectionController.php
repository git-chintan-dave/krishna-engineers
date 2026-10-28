<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Sections\IndexSectionRequest;
use App\Http\Requests\Sections\SectionRequest;
use App\Http\Resources\SectionResource;
use App\Imports\SectionsImport;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class SectionController extends Controller
{
    use HasCrudResponses, HasListingFilters;

    private const string MODULE_NAME = 'Section';

    private const string INDEX_ROUTE = 'sections.index';

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(IndexSectionRequest $request): Response {
        Gate::authorize('viewAny', Section::class);

        $filters = $request->validated();

        return Inertia::render('Sections/Index', [
            'sections' => Inertia::defer(function () use ($filters) {
                $sections = Section::query()
                    ->filterQuery($filters)
                    ->paginate($filters['per_page'] ?? 10)
                    ->withQueryString();

                return SectionResource::collection($sections);
            }),

            'hasAnyRecords' => Section::query()->exists(),

            'filters' => [
                ...$this->listingFilters($filters),
                'status' => $filters['status'] ?? 'all',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
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
    | Store
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize('create', Section::class);

        return Inertia::render('Sections/Create', []);
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

    public function import()
    {
        return Inertia::render('Sections/Import', []);
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);

        $import = new SectionsImport;

        Excel::import($import, $request->file('file'));

        return $this->successResponse(
            action: 'imported_with_counts',
            created: $import->getCreatedCount(),
            updated: $import->getUpdatedCount(),
        );
    }
}
