<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Tools\IndexToolRequest;
use App\Http\Requests\Tools\ToolRequest;
use App\Http\Resources\ToolResource;
use App\Models\Tool;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ToolController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const string MODULE_NAME = 'Tool';

    private const string INDEX_ROUTE = 'tools.index';

    private const string IMAGE_DIRECTORY = 'tools';

    public function __construct(private readonly FileUploadService $fileUploadService) {}

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(IndexToolRequest $request): Response
    {
        Gate::authorize('viewAny', Tool::class);

        $filters = $request->validated();

        $tools = Tool::query()
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Tools/Index', [
            'tools' => ToolResource::collection($tools),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'type' => $filters['type'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'id',
                'direction' => $filters['direction'] ?? 'desc',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize('create', Tool::class);

        return Inertia::render('Tools/Create', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Tool $tool): Response
    {
        Gate::authorize('view', $tool);

        return Inertia::render('Tools/Edit', [
            'tool' => new ToolResource($tool),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        ToolRequest $request,
        Tool $tool,
    ): RedirectResponse {
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

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(Tool $tool): RedirectResponse
    {
        Gate::authorize('delete', $tool);

        $tool->delete();

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
        Gate::authorize('deleteAny', Tool::class);

        Tool::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
