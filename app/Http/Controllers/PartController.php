<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Parts\IndexPartRequest;
use App\Http\Requests\Parts\PartRequest;
use App\Http\Resources\PartResource;
use App\Imports\PartsImport;
use App\Models\Part;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class PartController extends Controller
{
    use HasCrudResponses, HasListingFilters;

    private const string MODULE_NAME = 'Part';

    private const string INDEX_ROUTE = 'parts.index';

    private const string IMAGE_DIRECTORY = 'parts';

    public function __construct(private readonly FileUploadService $fileUploadService) {}

    public function index(IndexPartRequest $request): Response
    {
        Gate::authorize('viewAny', Part::class);

        $filters = $request->validated();

        return Inertia::render('Parts/Index', [
            'parts' => Inertia::defer(function () use ($filters) {
                $parts = Part::query()
                    ->with('section')
                    ->filterQuery($filters)
                    ->paginate($filters['per_page'] ?? 10)
                    ->withQueryString();

                return PartResource::collection($parts);
            }),

            'hasAnyRecords' => Part::query()->exists(),

            'filters' => [
                ...$this->listingFilters($filters),
                'status' => $filters['status'] ?? 'all',
                'sectionId' => $filters['section_id'] ?? 'all',
            ],
        ]);
    }

    public function store(PartRequest $request): RedirectResponse
    {
        Gate::authorize('create', Part::class);

        $data = $request->validated();

        $data['image'] = $this->fileUploadService->store(
            $request->file('image'),
            self::IMAGE_DIRECTORY,
        );

        Part::create($data);

        return $this->successResponse('created');
    }

    public function create(): Response
    {
        Gate::authorize('create', Part::class);

        return Inertia::render('Parts/Create', []);
    }

    public function edit(Part $part): Response
    {
        Gate::authorize('view', $part);

        $part->load('section');

        return Inertia::render('Parts/Edit', [
            'part' => new PartResource($part),
        ]);
    }

    public function update(
        PartRequest $request,
        Part $part,
    ): RedirectResponse {
        Gate::authorize('update', $part);

        $data = $request->validated();

        $data['image'] = $this->fileUploadService->update(
            $request->file('image'),
            $part->image,
            self::IMAGE_DIRECTORY,
            $request->boolean('remove_image'),
        );

        unset($data['remove_image']);

        $part->update($data);

        return $this->successResponse('updated');
    }

    public function destroy(Part $part): RedirectResponse
    {
        Gate::authorize('delete', $part);

        $part->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', Part::class);

        Part::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    public function import()
    {
        return Inertia::render('Parts/Import', []);
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

        $import = new PartsImport;

        Excel::import($import, $request->file('file'));

        return $this->successResponse(
            action: 'imported_with_counts',
            created: $import->getCreatedCount(),
            updated: $import->getUpdatedCount(),
        );
    }
}
