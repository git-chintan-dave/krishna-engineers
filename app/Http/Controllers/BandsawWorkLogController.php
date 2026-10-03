<?php

namespace App\Http\Controllers;

use App\Enums\RawMaterialTransactionType;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BandsawWorkLogs\BandsawWorkLogRequest;
use App\Http\Requests\BandsawWorkLogs\IndexBandsawWorkLogRequest;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Resources\BandsawWorkLogResource;
use App\Models\BandsawWorkLog;
use App\Models\RawMaterialTransaction;
use App\Models\SectionSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
        IndexBandsawWorkLogRequest $request
    ): Response {
        Gate::authorize(
            'viewAny',
            BandsawWorkLog::class
        );

        $filters = $request->validated();

        $bandsawWorkLogs = $this->applyIndexFilters(
            BandsawWorkLog::query()->with([
                'user',
                'part',
                'sectionSize.section',
            ]),
            $filters,
            [],
        )
            ->when(
                $filters['work_date'] ?? null,
                fn (
                    Builder $query,
                    string $workDate
                ): Builder => $query->whereDate(
                    'work_date',
                    $workDate
                )
            )
            ->when(
                $filters['user_id'] ?? null,
                fn (
                    Builder $query,
                    int $userId
                ): Builder => $query->where(
                    'user_id',
                    $userId
                )
            )
            ->when(
                $filters['part_id'] ?? null,
                fn (
                    Builder $query,
                    int $partId
                ): Builder => $query->where(
                    'part_id',
                    $partId
                )
            )
            ->when(
                $filters['section_id'] ?? null,
                fn (
                    Builder $query,
                    int $sectionId
                ): Builder => $query->whereHas(
                    'sectionSize',
                    fn (
                        Builder $query
                    ): Builder => $query->where(
                        'section_id',
                        $sectionId
                    )
                )
            )
            ->when(
                $filters['section_size_id'] ?? null,
                fn (
                    Builder $query,
                    int $sectionSizeId
                ): Builder => $query->where(
                    'section_size_id',
                    $sectionSizeId
                )
            )
            ->orderBy(
                $filters['sort'] ?? 'work_date',
                $filters['direction'] ?? 'desc'
            )
            ->paginate(
                $filters['per_page'] ?? 10
            )
            ->withQueryString();

        return Inertia::render(
            'WorkLogs/Bandsaw/Index',
            [
                'bandsawWorkLogs' =>
                    BandsawWorkLogResource::collection(
                        $bandsawWorkLogs
                    ),

                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'workDate' => $filters['work_date'] ?? '',
                    'userId' => $filters['user_id'] ?? 'all',
                    'partId' => $filters['part_id'] ?? 'all',
                    'sectionId' => $filters['section_id'] ?? 'all',
                    'sectionSizeId' =>
                        $filters['section_size_id'] ?? 'all',
                    'perPage' => $filters['per_page'] ?? 10,
                    'sort' => $filters['sort'] ?? 'work_date',
                    'direction' =>
                        $filters['direction'] ?? 'desc',
                ],
            ]
        );
    }

    /**
     * Show the form for creating a new Bandsaw work log.
     */
    public function create(): Response
    {
        Gate::authorize(
            'create',
            BandsawWorkLog::class
        );

        return Inertia::render(
            'WorkLogs/Bandsaw/Create',
            $this->formOptions(
                includeLastReports: true
            )
        );
    }

    /**
     * Store a newly created Bandsaw work log.
     */
    public function store(
        BandsawWorkLogRequest $request
    ): RedirectResponse {
        Gate::authorize(
            'create',
            BandsawWorkLog::class
        );

        DB::transaction(
            function () use ($request): void {
                $data = $request->validated();

                /*
                 * Section ID is used only by the form to select
                 * the section size. It is not stored on the work log.
                 */
                unset($data['section_id']);

                /*
                 * A Bandsaw user can only create a work log
                 * for themselves.
                 */
                $data = $this->resolveUserId($data);

                /*
                 * Lock the section size so concurrent work logs
                 * cannot consume the same stock simultaneously.
                 */
                $sectionSize = SectionSize::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['section_size_id']
                    );

                $quantity = (int) $data['quantity'];

                $this->ensureStockAvailable(
                    $sectionSize,
                    $quantity
                );

                /*
                 * Create the work log.
                 */
                $bandsawWorkLog =
                    BandsawWorkLog::create($data);

                /*
                 * Consume raw material stock.
                 */
                $sectionSize->decrement(
                    'stock_quantity',
                    $quantity
                );

                /*
                 * Create the corresponding outward
                 * inventory transaction.
                 */
                $this->createOutwardTransaction(
                    $bandsawWorkLog
                );
            }
        );

        return $this->successResponse('created');
    }

    /**
     * Show the form for editing the specified Bandsaw work log.
     */
    public function edit(
        BandsawWorkLog $bandsaw
    ): Response {
        Gate::authorize(
            'view',
            $bandsaw
        );

        $bandsaw->load([
            'user',
            'part',
            'sectionSize.section',
        ]);

        return Inertia::render(
            'WorkLogs/Bandsaw/Edit',
            [
                'bandsawWorkLog' =>
                    new BandsawWorkLogResource(
                        $bandsaw
                    ),

                ...$this->formOptions(
                    includeLastReports: true
                ),
            ]
        );
    }

    /**
     * Update the specified Bandsaw work log.
     */
    public function update(
        BandsawWorkLogRequest $request,
        BandsawWorkLog $bandsaw
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $bandsaw
        );

        DB::transaction(
            function () use (
                $request,
                $bandsaw
            ): void {
                $data = $request->validated();

                /*
                 * Section ID is used only by the form to select
                 * the section size. It is not stored on the work log.
                 */
                unset($data['section_id']);

                /*
                 * A Bandsaw user can only update a work log
                 * for themselves.
                 */
                $data = $this->resolveUserId($data);

                $oldSectionSizeId =
                    (int) $bandsaw->section_size_id;

                $oldQuantity =
                    (int) $bandsaw->quantity;

                $newSectionSizeId =
                    (int) $data['section_size_id'];

                $newQuantity =
                    (int) $data['quantity'];

                /*
                 * Lock all affected section sizes in deterministic
                 * order.
                 */
                $sectionSizeIds = collect([
                    $oldSectionSizeId,
                    $newSectionSizeId,
                ])
                    ->unique()
                    ->sort()
                    ->values();

                $sectionSizes = SectionSize::query()
                    ->whereIn(
                        'id',
                        $sectionSizeIds
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $oldSectionSize =
                    $sectionSizes->get(
                        $oldSectionSizeId
                    );

                $newSectionSize =
                    $sectionSizes->get(
                        $newSectionSizeId
                    );

                if (
                    ! $oldSectionSize ||
                    ! $newSectionSize
                ) {
                    abort(404);
                }

                /*
                 * Return the material consumed by the old
                 * work log.
                 */
                $oldSectionSize->increment(
                    'stock_quantity',
                    $oldQuantity
                );

                /*
                 * Make sure the new quantity can be consumed.
                 */
                $this->ensureStockAvailable(
                    $newSectionSize,
                    $newQuantity
                );

                /*
                 * Consume material for the updated work log.
                 */
                $newSectionSize->decrement(
                    'stock_quantity',
                    $newQuantity
                );

                /*
                 * Update the work log.
                 */
                $bandsaw->update($data);

                /*
                 * Keep the generated inventory transaction
                 * synchronized.
                 */
                $this->updateOutwardTransaction(
                    $bandsaw
                );
            }
        );

        return $this->successResponse('updated');
    }

    /**
     * Remove the specified Bandsaw work log.
     */
    public function destroy(
        BandsawWorkLog $bandsaw
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $bandsaw
        );

        DB::transaction(
            function () use ($bandsaw): void {
                /*
                 * Lock the section size before returning stock.
                 */
                $sectionSize = SectionSize::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $bandsaw->section_size_id
                    );

                /*
                 * Return the consumed material to stock.
                 */
                $sectionSize->increment(
                    'stock_quantity',
                    (int) $bandsaw->quantity
                );

                /*
                 * Remove the generated outward transaction.
                 */
                $this->deleteOutwardTransaction(
                    $bandsaw
                );

                /*
                 * Finally remove the work log.
                 */
                $bandsaw->delete();
            }
        );

        return $this->successResponse('deleted');
    }

    /**
     * Remove multiple Bandsaw work logs.
     */
    public function bulkDestroy(
        BulkDestroyRequest $request
    ): RedirectResponse {
        Gate::authorize(
            'deleteAny',
            BandsawWorkLog::class
        );

        DB::transaction(
            function () use ($request): void {
                $ids = $request->validated('ids');

                $bandsawWorkLogs = BandsawWorkLog::query()
                    ->whereIn('id', $ids)
                    ->get([
                        'id',
                        'section_size_id',
                        'quantity',
                    ]);

                if ($bandsawWorkLogs->isEmpty()) {
                    return;
                }

                /*
                 * Lock all affected section sizes.
                 */
                $sectionSizeIds = $bandsawWorkLogs
                    ->pluck('section_size_id')
                    ->unique()
                    ->sort()
                    ->values();

                $sectionSizes = SectionSize::query()
                    ->whereIn(
                        'id',
                        $sectionSizeIds
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                /*
                 * Calculate the total quantity to return
                 * for each section size.
                 */
                $quantitiesBySectionSize =
                    $bandsawWorkLogs
                        ->groupBy('section_size_id')
                        ->map(
                            fn ($logs): int =>
                            $logs->sum('quantity')
                        );

                foreach (
                    $quantitiesBySectionSize
                    as $sectionSizeId => $quantity
                ) {
                    $sectionSize =
                        $sectionSizes->get(
                            (int) $sectionSizeId
                        );

                    if (! $sectionSize) {
                        abort(404);
                    }

                    $sectionSize->increment(
                        'stock_quantity',
                        $quantity
                    );
                }

                /*
                 * Remove all inventory transactions generated
                 * by these Bandsaw work logs.
                 */
                $this->deleteOutwardTransactions(
                    $bandsawWorkLogs->pluck('id')
                );

                /*
                 * Remove the work logs.
                 */
                BandsawWorkLog::query()
                    ->whereIn(
                        'id',
                        $bandsawWorkLogs->pluck('id')
                    )
                    ->delete();
            }
        );

        return $this->successResponse('bulk_deleted');
    }

    /**
     * Get options required by the Bandsaw work log forms.
     */
    private function formOptions(
        bool $includeLastReports = false
    ): array {
        if (! $includeLastReports) {
            return [];
        }

        $currentUser = Auth::user();

        $role = $currentUser->role instanceof UserRole
            ? $currentUser->role->value
            : $currentUser->role;

        $canSelectBandsaw = in_array(
            $role,
            [
                UserRole::SUPER_ADMIN->value,
                UserRole::SUPERVISOR->value,
            ],
            true
        );

        $bandsawIds = \App\Models\User::query()
            ->where(
                'role',
                UserRole::BANDSAW->value
            )
            ->where(
                'status',
                \App\Enums\Status::ACTIVE->value
            )
            ->pluck('id');

        if ($role === UserRole::BANDSAW->value) {
            $bandsawIds->push(
                $currentUser->id
            );
        }

        $lastReports = BandsawWorkLog::query()
            ->with(
                'sectionSize:id,section_id'
            )
            ->whereIn(
                'user_id',
                $bandsawIds->unique()
            )
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->get([
                'user_id',
                'part_id',
                'section_size_id',
            ])
            ->unique('user_id')
            ->mapWithKeys(
                fn (
                    BandsawWorkLog $log
                ): array => [
                    (string) $log->user_id => [
                        'part_id' => $log->part_id,
                        'section_id' =>
                            $log->sectionSize?->section_id,
                        'section_size_id' =>
                            $log->section_size_id,
                    ],
                ]
            )
            ->all();

        return [
            'canSelectBandsaw' =>
                $canSelectBandsaw,

            'lastReports' =>
                $lastReports,
        ];
    }

    /**
     * Resolve the Bandsaw user ID.
     */
    private function resolveUserId(
        array $data
    ): array {
        $currentUser = Auth::user();

        $role = $currentUser->role instanceof UserRole
            ? $currentUser->role
            : UserRole::tryFrom(
                (string) $currentUser->role
            );

        if ($role === UserRole::BANDSAW) {
            $data['user_id'] =
                $currentUser->id;
        }

        return $data;
    }

    /**
     * Ensure sufficient raw material stock is available.
     */
    private function ensureStockAvailable(
        SectionSize $sectionSize,
        int $quantity
    ): void {
        $availableStock =
            (int) $sectionSize->stock_quantity;

        if ($availableStock >= $quantity) {
            return;
        }

        throw ValidationException::withMessages([
            'quantity' => sprintf(
                'Insufficient stock. Available stock for this section size is %d.',
                $availableStock
            ),
        ]);
    }

    /**
     * Create the outward raw material transaction.
     */
    private function createOutwardTransaction(
        BandsawWorkLog $bandsawWorkLog
    ): void {
        $transaction = RawMaterialTransaction::create([
            'section_size_id' =>
                $bandsawWorkLog->section_size_id,

            'type' =>
                RawMaterialTransactionType::OUTWARD,

            'quantity' =>
                $bandsawWorkLog->quantity,

            'user_id' =>
                $bandsawWorkLog->user_id,

            'remarks' =>
                'Material consumed by Bandsaw Work Log.',
        ]);

        $transaction->reference()->associate(
            $bandsawWorkLog
        );

        $transaction->save();
    }

    /**
     * Update the outward raw material transaction.
     */
    private function updateOutwardTransaction(
        BandsawWorkLog $bandsawWorkLog
    ): void {
        $transaction = RawMaterialTransaction::query()
            ->whereMorphedTo(
                'reference',
                $bandsawWorkLog
            )
            ->first();

        if (! $transaction) {
            $this->createOutwardTransaction(
                $bandsawWorkLog
            );

            return;
        }

        $transaction->update([
            'section_size_id' =>
                $bandsawWorkLog->section_size_id,

            'type' =>
                RawMaterialTransactionType::OUTWARD,

            'quantity' =>
                $bandsawWorkLog->quantity,

            'user_id' =>
                $bandsawWorkLog->user_id,
        ]);
    }

    /**
     * Delete the outward raw material transaction.
     */
    private function deleteOutwardTransaction(
        BandsawWorkLog $bandsawWorkLog
    ): void {
        RawMaterialTransaction::query()
            ->whereMorphedTo(
                'reference',
                $bandsawWorkLog
            )
            ->delete();
    }

    /**
     * Delete outward raw material transactions
     * generated by multiple work logs.
     */
    private function deleteOutwardTransactions(
        $bandsawWorkLogIds
    ): void {
        RawMaterialTransaction::query()
            ->where(
                'reference_type',
                (new RawMaterialTransaction)
                    ->getMorphClass()
            )
            ->whereIn(
                'reference_id',
                $bandsawWorkLogIds
            )
            ->delete();
    }
}
