<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\SectionSizes\IndexSectionSizeRequest;
use App\Http\Requests\SectionSizes\SectionSizeRequest;
use App\Http\Resources\SectionSizeResource;
use App\Imports\SectionSizesImport;
use App\Models\SectionSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class SectionSizeController extends Controller
{
    use HasCrudResponses, HasListingFilters;

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
        $lowStockThreshold = (int) config('inventory.low_stock_threshold', 10);

        return Inertia::render('SectionSizes/Index', [
            'sectionSizes' => Inertia::defer(function () use ($filters, $lowStockThreshold) {
                $stockFilter = $filters['stock'] ?? null;
                $sectionSizes = SectionSize::query()
                    ->with('section')
                    ->filterQuery($filters)
                    ->when(
                        $stockFilter === 'low_stock',
                        fn ($query) => $query
                            ->where('quantity', '>', 0)
                            ->where('quantity', '<=', $lowStockThreshold),
                    )
                    ->when(
                        $stockFilter === 'out_of_stock',
                        fn ($query) => $query->where('quantity', 0),
                    )
                    ->paginate($filters['per_page'] ?? 10)
                    ->withQueryString();

                return SectionSizeResource::collection($sectionSizes);
            }),
            'statistics' => Inertia::defer(
                fn () => $this->getStatistics(),
            ),
            'low_stock_threshold' => $lowStockThreshold,
            'hasAnyRecords' => SectionSize::query()->exists(),
            'filters' => [
                ...$this->listingFilters($filters),
                'sectionId' => $filters['section_id'] ?? 'all',
                'stock' => $filters['stock'] ?? null,
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

        $import = new SectionSizesImport;

        Excel::import($import, $request->file('file'));

        return $this->successResponse(
            action: 'imported_with_counts',
            created: $import->getCreatedCount(),
            updated: $import->getUpdatedCount(),
        );
    }

    public function import()
    {
        return Inertia::render('SectionSizes/Import', []);
    }

    private function getStatistics(): array
    {
        $query = SectionSize::query();

        return [
            'total' => (clone $query)->count(),

            'total_sections' => (clone $query)
                ->distinct('section_id')
                ->count('section_id'),

            'total_stock' => (clone $query)->sum('quantity'),

            'in_stock' => (clone $query)
                ->where('quantity', '>', 0)
                ->count(),

            'out_of_stock' => (clone $query)
                ->where('quantity', 0)
                ->count(),
        ];
    }
}
