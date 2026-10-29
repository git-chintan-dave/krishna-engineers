<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Tools\IndexToolRequest;
use App\Http\Requests\Tools\ToolRequest;
use App\Http\Resources\ToolResource;
use App\Imports\ToolsImport;
use App\Models\Tool;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ToolController extends Controller
{
    use HasCrudResponses, HasListingFilters;

    private const string IMAGE_DIRECTORY = 'tools';

    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {}

    public function index(IndexToolRequest $request): Response
    {
        Gate::authorize('viewAny', Tool::class);

        $filters = $request->validated();
        $lowStockThreshold = (int) config('inventory.low_stock_threshold', 10);

        return Inertia::render('Tools/Index', [
            'tools' => Inertia::defer(
                fn () => $this->getFilteredTools($filters, $lowStockThreshold),
            ),
            'statistics' => Inertia::defer(
                fn () => $this->getStatistics($lowStockThreshold),
            ),
            'low_stock_threshold' => $lowStockThreshold,
            'hasAnyRecords' => Tool::query()->exists(),
            'filters' => [
                ...$this->listingFilters($filters),
                'type' => $filters['type'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
                'stock' => $filters['stock'] ?? null,
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Tool::class);

        return Inertia::render('Tools/Create', []);
    }

    public function store(ToolRequest $request): RedirectResponse
    {
        Gate::authorize('create', Tool::class);

        $data = $request->validated();
        $data['image'] = $this->fileUploadService->store(
            $request->file('image'),
            self::IMAGE_DIRECTORY,
        );

        Tool::create($data);

        return $this->successResponse('created');
    }

    public function edit(Tool $tool): Response
    {
        Gate::authorize('view', $tool);

        return Inertia::render('Tools/Edit', [
            'tool' => new ToolResource($tool),
        ]);
    }

    public function update(ToolRequest $request, Tool $tool): RedirectResponse {
        Gate::authorize('update', $tool);

        $data = $request->validated();
        $data['image'] = $this->fileUploadService->update(
            $request->file('image'),
            $tool->image,
            self::IMAGE_DIRECTORY,
            $request->boolean('remove_image'),
        );

        unset($data['remove_image']);

        $tool->update($data);

        return $this->successResponse('updated');
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        Gate::authorize('delete', $tool);

        $tool->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse {
        Gate::authorize('deleteAny', Tool::class);

        Tool::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    public function import(): Response
    {
        return Inertia::render('Tools/Import', []);
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);

        $import = new ToolsImport;

        Excel::import($import, $request->file('file'));

        return $this->successResponse(
            action: 'imported_with_counts',
            created: $import->getCreatedCount(),
            updated: $import->getUpdatedCount(),
        );
    }

    private function getFilteredTools(array $filters, int $lowStockThreshold): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        $queryFilters = $filters;
        $stockFilter = $queryFilters['stock'] ?? null;

        unset($queryFilters['stock']);

        $tools = Tool::query()
            ->filterQuery($queryFilters)
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

        return ToolResource::collection($tools);
    }

    private function getStatistics(int $lowStockThreshold): array
    {
        $stats = Tool::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                'SUM(CASE WHEN quantity > 0 AND quantity <= ? THEN 1 ELSE 0 END) AS low_stock',
                [$lowStockThreshold],
            )
            ->selectRaw(
                'SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock',
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active",
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive",
            )
            ->selectRaw(
                "SUM(CASE WHEN type = 'insert' THEN 1 ELSE 0 END) AS inserts",
            )
            ->selectRaw(
                "SUM(CASE WHEN type = 'drill' THEN 1 ELSE 0 END) AS drill",
            )
            ->selectRaw(
                "SUM(CASE WHEN type = 'endmill' THEN 1 ELSE 0 END) AS endmill",
            )
            ->selectRaw(
                "SUM(CASE WHEN type = 'special_tools' THEN 1 ELSE 0 END) AS special_tools",
            )
            ->firstOrFail();

        return [
            'total' => (int) $stats->total,
            'low_stock' => (int) $stats->low_stock,
            'out_of_stock' => (int) $stats->out_of_stock,
            'active' => (int) $stats->active,
            'inactive' => (int) $stats->inactive,
            'insert' => (int) $stats->inserts,
            'drill' => (int) $stats->drill,
            'endmill' => (int) $stats->endmill,
            'special_tools' => (int) $stats->special_tools,
        ];
    }
}
