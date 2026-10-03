<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\PartSetups\IndexPartSetupRequest;
use App\Http\Requests\PartSetups\PartSetupRequest;
use App\Http\Resources\PartSetupResource;
use App\Models\PartSetup;
use App\Services\FileUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PartSetupController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Part Setup';

    private const string INDEX_ROUTE = 'part-setups.index';

    private const string IMAGE_DIRECTORY = 'part-setups';

    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        IndexPartSetupRequest $request,
    ): Response {
        Gate::authorize(
            'viewAny',
            PartSetup::class,
        );

        $filters = $request->validated();

        $query = PartSetup::query()
            ->with([
                'part',
                'setup',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Relationship Sorting
        |--------------------------------------------------------------------------
        */

        if (($filters['sort'] ?? null) === 'part') {
            $query
                ->leftJoin(
                    'parts',
                    'parts.id',
                    '=',
                    'part_setups.part_id',
                )
                ->orderBy(
                    'parts.name',
                    $filters['direction'] ?? 'asc',
                )
                ->select('part_setups.*');
        } elseif (($filters['sort'] ?? null) === 'setup') {
            $query
                ->leftJoin(
                    'setups',
                    'setups.id',
                    '=',
                    'part_setups.setup_id',
                )
                ->orderBy(
                    'setups.name',
                    $filters['direction'] ?? 'asc',
                )
                ->select('part_setups.*');
        } else {
            $query = $this->applyIndexFilters(
                $query,
                $filters,
                [
                    'machine_cycle_time_seconds',
                    'actual_cycle_time_seconds',
                ],
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $partSetups = $query
            ->when(
                $filters['part_id'] ?? null,
                fn (
                    Builder $query,
                    int $partId,
                ): Builder => $query->where(
                    'part_setups.part_id',
                    $partId,
                ),
            )
            ->when(
                $filters['setup_id'] ?? null,
                fn (
                    Builder $query,
                    int $setupId,
                ): Builder => $query->where(
                    'part_setups.setup_id',
                    $setupId,
                ),
            )
            ->paginate(
                $filters['per_page'] ?? 10,
            )
            ->withQueryString();

        return Inertia::render(
            'PartSetups/Index',
            [
                'partSetups' => PartSetupResource::collection(
                    $partSetups,
                ),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'partId' => $filters['part_id'] ?? null,
                    'setupId' => $filters['setup_id'] ?? null,
                    'perPage' => $filters['per_page'] ?? 10,
                    'sort' => $filters['sort'] ?? 'created_at',
                    'direction' => $filters['direction'] ?? 'desc',
                ],
            ],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize(
            'create',
            PartSetup::class,
        );

        return Inertia::render('PartSetups/Create', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        PartSetupRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'create',
            PartSetup::class,
        );

        $data = $request->validated();

        $data['machine_cycle_time_seconds'] =
            $data['machine_cycle_time'];

        $data['actual_cycle_time_seconds'] =
            $data['actual_cycle_time'];

        unset(
            $data['machine_cycle_time'],
            $data['actual_cycle_time'],
        );

        $data['image'] = $this->fileUploadService->store(
            $request->file('image'),
            self::IMAGE_DIRECTORY,
        );

        unset($data['remove_image']);

        PartSetup::create($data);

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        PartSetup $partSetup,
    ): Response {
        Gate::authorize(
            'view',
            $partSetup,
        );

        $partSetup->load([
            'part',
            'setup',
        ]);

        return Inertia::render('PartSetups/Edit', [
            'partSetup' => new PartSetupResource(
                $partSetup,
            )],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        PartSetupRequest $request,
        PartSetup $partSetup,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $partSetup,
        );

        $data = $request->validated();

        $data['machine_cycle_time_seconds'] =
            $data['machine_cycle_time'];

        $data['actual_cycle_time_seconds'] =
            $data['actual_cycle_time'];

        unset(
            $data['machine_cycle_time'],
            $data['actual_cycle_time'],
        );

        $data['image'] = $this->fileUploadService->update(
            $request->file('image'),
            $partSetup->image,
            self::IMAGE_DIRECTORY,
            $request->boolean('remove_image'),
        );

        unset($data['remove_image']);

        $partSetup->update($data);

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        PartSetup $partSetup,
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $partSetup,
        );

        $this->fileUploadService->delete(
            $partSetup->image,
            self::IMAGE_DIRECTORY,
        );

        $partSetup->delete();

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
        Gate::authorize(
            'deleteAny',
            PartSetup::class,
        );

        $partSetups = PartSetup::query()
            ->whereIn(
                'id',
                $request->validated('ids'),
            )
            ->get();

        foreach ($partSetups as $partSetup) {
            $this->fileUploadService->delete(
                $partSetup->image,
                self::IMAGE_DIRECTORY,
            );
        }

        PartSetup::query()
            ->whereIn(
                'id',
                $request->validated('ids'),
            )
            ->delete();

        return $this->successResponse(
            'bulk_deleted',
        );
    }
}
