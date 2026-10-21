<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\SectionSizes\IndexSectionSizeRequest;
use App\Http\Requests\SectionSizes\SectionSizeRequest;
use App\Http\Resources\SectionSizeResource;
use App\Models\SectionSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SectionSizeController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Section Size';

    private const string INDEX_ROUTE = 'section-sizes.index';

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(IndexSectionSizeRequest $request): Response
    {
        Gate::authorize('viewAny', SectionSize::class);

        $filters = $request->validated();

        $sectionSizes = SectionSize::query()
            ->with('section')
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('SectionSizes/Index', [
            'sectionSizes' => SectionSizeResource::collection($sectionSizes),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sectionId' => $filters['section_id'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'size',
                'direction' => $filters['direction'] ?? 'asc',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function store(SectionSizeRequest $request): RedirectResponse
    {
        Gate::authorize('create', SectionSize::class);

        SectionSize::create($request->validated());

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize('create', SectionSize::class);

        return Inertia::render('SectionSizes/Create', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(SectionSize $sectionSize): Response
    {
        Gate::authorize('view', $sectionSize);

        return Inertia::render('SectionSizes/Edit', [
            'sectionSize' => new SectionSizeResource(
                $sectionSize->load('section'),
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        SectionSizeRequest $request,
        SectionSize        $sectionSize,
    ): RedirectResponse
    {
        Gate::authorize('update', $sectionSize);

        $sectionSize->update($request->validated());

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(SectionSize $sectionSize): RedirectResponse
    {
        Gate::authorize('delete', $sectionSize);

        $sectionSize->delete();

        return $this->successResponse('deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Destroy
    |--------------------------------------------------------------------------
    */

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse
    {
        Gate::authorize('deleteAny', SectionSize::class);

        SectionSize::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
