<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Parts\IndexPartRequest;
use App\Http\Requests\Parts\PartRequest;
use App\Http\Resources\PartResource;
use App\Models\Part;
use App\Services\FileUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PartController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Part';

    private const string INDEX_ROUTE = 'parts.index';

    private const string IMAGE_DIRECTORY = 'parts';

    public function __construct(private readonly FileUploadService $fileUploadService)
    {
    }

    public function index(IndexPartRequest $request): Response
    {
        Gate::authorize('viewAny', Part::class);

        $filters = $request->validated();

        $parts = $this->applyIndexFilters(
            Part::query(),
            $filters,
            ['name', 'new_part_name', 'cutting_size', 'part_weight', 'raw_material_weight'],
        )
            ->with('section')
            ->when(
                $filters['section_id'] ?? null,
                fn(Builder $query, int $sectionId): Builder => $query->where(
                    'section_id',
                    $sectionId,
                ),
            )
            ->when(
                $filters['status'] ?? null,
                fn(
                    Builder $query,
                    string  $status,
                ): Builder => $query->where(
                    'status',
                    $status,
                ),
            )
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Parts/Index', [
            'parts' => PartResource::collection($parts),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sectionId' => $filters['section_id'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'name',
                'direction' => $filters['direction'] ?? 'asc',
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
        Part        $part,
    ): RedirectResponse
    {
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
    ): RedirectResponse
    {
        Gate::authorize('deleteAny', Part::class);

        Part::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
