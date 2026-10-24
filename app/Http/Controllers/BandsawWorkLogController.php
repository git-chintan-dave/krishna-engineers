<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BandsawWorkLogs\BandsawWorkLogRequest;
use App\Http\Requests\BandsawWorkLogs\IndexBandsawWorkLogRequest;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Resources\BandsawWorkLogResource;
use App\Models\BandsawWorkLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BandsawWorkLogController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Bandsaw Work Log';

    private const string INDEX_ROUTE = 'bandsaw.index';

    /**
     * Display a listing of Bandsaw work logs.
     */
    public function index(
        IndexBandsawWorkLogRequest $request,
    ): Response {
        Gate::authorize(
            'viewAny',
            BandsawWorkLog::class,
        );

        $user = $request->user();
        $filters = $request->validated();

        $isBandsaw = $user->role === UserRole::BANDSAW;

        $bandsawWorkLogs = BandsawWorkLog::query()
            ->select('bandsaw_work_logs.*')
            ->with([
                'user',
                'part',
                'sectionSize.section',
            ])
            ->when($isBandsaw, fn (Builder $query): Builder => $query->forUser($user->id))
            ->when(
                ! $isBandsaw,
                fn (Builder $query): Builder => $query->filterQuery($filters),
            )
            ->when($isBandsaw, fn (Builder $query): Builder => $query->filterQuery(
                array_diff_key($filters, ['user_id' => true]),
            ))
            ->when($filters['section_id'] ?? null, fn (Builder $query, int $sectionId): Builder => $query
                ->whereHas(
                    'sectionSize',
                    fn (Builder $query): Builder => $query
                        ->where('section_id', $sectionId),
                ),
            )
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('WorkLogs/Bandsaw/Index', [
            'bandsawWorkLogs' => BandsawWorkLogResource::collection($bandsawWorkLogs),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'workDate' => $filters['work_date'] ?? '',
                'userId' => $filters['user_id'] ?? 'all',
                'partId' => $filters['part_id'] ?? 'all',
                'sectionId' => $filters['section_id'] ?? 'all',
                'sectionSizeId' => $filters['section_size_id'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'id',
                'direction' => $filters['direction'] ?? 'desc',
            ],
            ...$this->formOptions(),
        ]);
    }

    /**
     * Get options required by the Bandsaw work log forms.
     */
    private function formOptions(
        bool $includeLastReports = false,
    ): array {
        $currentUser = Auth::user();

        $role = $currentUser->role instanceof UserRole
            ? $currentUser->role->value
            : $currentUser->role;

        $isBandsaw = $role === UserRole::BANDSAW->value;

        $canSelectBandsaw = in_array(
            $role,
            [
                UserRole::SUPER_ADMIN->value,
                UserRole::SUPERVISOR->value,
            ],
            true,
        );

        $options = [
            'currentUserId' => $currentUser->id,
            'canSelectBandsaw' => $canSelectBandsaw,
        ];

        if (! $includeLastReports) {
            return $options;
        }

        /*
         * Last report per Bandsaw operator is used to make creating
         * the next work log faster by pre-filling part, section
         * and section size.
         */
        $bandsawIds = User::query()
            ->where('role', UserRole::BANDSAW->value)
            ->where('status', Status::ACTIVE->value)
            ->pluck('id');

        /*
         * Include the logged-in Bandsaw operator even if their
         * account is currently inactive.
         */
        if ($isBandsaw) {
            $bandsawIds->push($currentUser->id);
        }

        $lastReports = BandsawWorkLog::query()
            ->with([
                'sectionSize:id,section_id',
            ])
            ->whereIn(
                'user_id',
                $bandsawIds->unique(),
            )
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'user_id',
                'part_id',
                'section_size_id',
            ])
            ->unique('user_id')
            ->mapWithKeys(
                fn (BandsawWorkLog $log): array => [
                    (string) $log->user_id => [
                        'part_id' => $log->part_id,
                        'section_id' => $log->sectionSize?->section_id,
                        'section_size_id' => $log->section_size_id,
                    ],
                ],
            )
            ->all();

        return [
            ...$options,
            'lastReports' => $lastReports,
        ];
    }

    /**
     * Store a newly created Bandsaw work log.
     */
    public function store(
        BandsawWorkLogRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'create',
            BandsawWorkLog::class,
        );

        $data = $request->validated();

        /*
         * Section ID is used only by the form to
         * select the section size. It is not stored.
         */
        unset($data['section_id']);

        /*
         * A Bandsaw user can only create a work log
         * for themselves.
         */
        $data = $this->resolveUserId($data);

        BandsawWorkLog::create($data);

        return $this->successResponse('created');
    }

    /**
     * Resolve the Bandsaw user ID.
     */
    private function resolveUserId(
        array $data,
        ?BandsawWorkLog $bandsaw = null,
    ): array {
        $currentUser = Auth::user();

        $role = $currentUser->role instanceof UserRole
            ? $currentUser->role
            : UserRole::tryFrom((string) $currentUser->role);

        if ($role === UserRole::BANDSAW) {
            $data['user_id'] = $bandsaw?->user_id
                ?? $currentUser->id;
        }

        return $data;
    }

    /**
     * Show the form for creating a new Bandsaw work log.
     */
    public function create(): Response
    {
        Gate::authorize(
            'create',
            BandsawWorkLog::class,
        );

        return Inertia::render(
            'WorkLogs/Bandsaw/Create',
            $this->formOptions(
                includeLastReports: true,
            ),
        );
    }

    /**
     * Show the form for editing the specified Bandsaw work log.
     */
    public function edit(
        BandsawWorkLog $bandsaw,
    ): Response {
        Gate::authorize(
            'view',
            $bandsaw,
        );

        $bandsaw->load([
            'user',
            'part',
            'sectionSize.section',
        ]);

        return Inertia::render(
            'WorkLogs/Bandsaw/Edit',
            [
                'bandsawWorkLog' => new BandsawWorkLogResource(
                    $bandsaw,
                ),
                ...$this->formOptions(
                    includeLastReports: true,
                ),
            ],
        );
    }

    /**
     * Update the specified Bandsaw work log.
     */
    public function update(
        BandsawWorkLogRequest $request,
        BandsawWorkLog $bandsaw,
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $bandsaw,
        );

        $data = $request->validated();

        /*
         * Section ID is used only by the form to
         * select the section size. It is not stored.
         */
        unset($data['section_id']);

        /*
         * A Bandsaw user cannot change the employee
         * assigned to the work log.
         *
         * Supervisors and Super Admins may retain the
         * selected employee from the request.
         */
        $data = $this->resolveUserId(
            $data,
            $bandsaw,
        );

        $bandsaw->update($data);

        return $this->successResponse('updated');
    }

    /**
     * Remove the specified Bandsaw work log.
     */
    public function destroy(
        BandsawWorkLog $bandsaw,
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $bandsaw,
        );

        $bandsaw->delete();

        return $this->successResponse('deleted');
    }

    /**
     * Remove multiple Bandsaw work logs.
     */
    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'deleteAny',
            BandsawWorkLog::class,
        );

        BandsawWorkLog::query()
            ->whereIn(
                'id',
                $request->validated('ids'),
            )
            ->delete();

        return $this->successResponse('bulk_deleted');
    }
}
