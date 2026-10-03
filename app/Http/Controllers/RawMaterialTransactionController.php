<?php

namespace App\Http\Controllers;

use App\Enums\RawMaterialTransactionType;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\RawMaterialTransactions\IndexRawMaterialTransactionRequest;
use App\Http\Requests\RawMaterialTransactions\RawMaterialTransactionRequest;
use App\Http\Resources\RawMaterialTransactionResource;
use App\Models\RawMaterialTransaction;
use App\Models\SectionSize;
use App\Support\FlashMessage;
use App\Helpers\ResponseHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RawMaterialTransactionController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Raw Material Transaction';

    private const string INDEX_ROUTE = 'raw-material-transactions.index';

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        IndexRawMaterialTransactionRequest $request
    ): Response {
        Gate::authorize(
            'viewAny',
            RawMaterialTransaction::class
        );

        $filters = $request->validated();

        $transactions = RawMaterialTransaction::query()
            ->with([
                'sectionSize.section',
                'user',
                'reference',
            ])
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search
                ): void {
                    $query->where(function (
                        Builder $query
                    ) use ($search): void {
                        $query
                            ->whereHas(
                                'sectionSize',
                                fn (Builder $query) => $query
                                    ->where(
                                        'size',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereHas(
                                        'section',
                                        fn (Builder $query) => $query->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                    )
                            )
                            ->orWhereHas(
                                'user',
                                fn (Builder $query) => $query
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'employee_code',
                                        'like',
                                        "%{$search}%"
                                    )
                            )
                            ->orWhere(
                                'reference_type',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'reference_id',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'remarks',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
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
            ->when(
                $filters['type'] ?? null,
                fn (
                    Builder $query,
                    string $type
                ): Builder => $query->where(
                    'type',
                    $type
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
                $filters['from_date'] ?? null,
                fn (
                    Builder $query,
                    string $fromDate
                ): Builder => $query->whereDate(
                    'created_at',
                    '>=',
                    $fromDate
                )
            )
            ->when(
                $filters['to_date'] ?? null,
                fn (
                    Builder $query,
                    string $toDate
                ): Builder => $query->whereDate(
                    'created_at',
                    '<=',
                    $toDate
                )
            )
            ->orderBy(
                $filters['sort'] ?? 'created_at',
                $filters['direction'] ?? 'desc'
            )
            ->paginate(
                $filters['per_page'] ?? 10
            )
            ->withQueryString();

        return Inertia::render(
            'RawMaterialTransactions/Index',
            [
                'transactions' => RawMaterialTransactionResource::collection(
                    $transactions
                ),

                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'sectionSizeId' => $filters['section_size_id'] ?? 'all',
                    'type' => $filters['type'] ?? 'all',
                    'userId' => $filters['user_id'] ?? 'all',
                    'fromDate' => $filters['from_date'] ?? '',
                    'toDate' => $filters['to_date'] ?? '',
                    'perPage' => $filters['per_page'] ?? 10,
                    'sort' => $filters['sort'] ?? 'created_at',
                    'direction' => $filters['direction'] ?? 'desc',
                ],

                ...$this->formOptions(),
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
            RawMaterialTransaction::class
        );

        return Inertia::render(
            'RawMaterialTransactions/Create',
            $this->formOptions(),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        RawMaterialTransactionRequest $request
    ): RedirectResponse {
        Gate::authorize(
            'create',
            RawMaterialTransaction::class
        );

        DB::transaction(function () use ($request): void {
            $data = $request->validated();

            $sectionSize = SectionSize::query()
                ->lockForUpdate()
                ->findOrFail($data['section_size_id']);

            $quantity = (int) $data['quantity'];

            /*
             * Apply the stock movement before creating the transaction.
             */
            $this->applyStockMovement(
                $sectionSize,
                $data['type'],
                $quantity
            );

            /*
             * The transaction user is always the authenticated user.
             */
            RawMaterialTransaction::create([
                ...$data,
                'user_id' => $request->user()->id,
            ]);
        });

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        RawMaterialTransaction $rawMaterialTransaction
    ): Response {
        Gate::authorize(
            'view',
            $rawMaterialTransaction
        );

        $rawMaterialTransaction->load([
            'sectionSize.section',
            'user',
            'reference',
        ]);

        return Inertia::render(
            'RawMaterialTransactions/Edit',
            [
                'transaction' => new RawMaterialTransactionResource(
                    $rawMaterialTransaction
                ),

                ...$this->formOptions(),
            ],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        RawMaterialTransactionRequest $request,
        RawMaterialTransaction $rawMaterialTransaction
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $rawMaterialTransaction
        );

        DB::transaction(function () use (
            $request,
            $rawMaterialTransaction
        ): void {
            $data = $request->validated();

            $oldSectionSizeId = (int) $rawMaterialTransaction->section_size_id;
            $oldQuantity = (int) $rawMaterialTransaction->quantity;
            $oldType = $rawMaterialTransaction->type;

            $newSectionSizeId = (int) $data['section_size_id'];
            $newQuantity = (int) $data['quantity'];
            $newType = $data['type'];

            /*
             * Lock all affected section sizes in deterministic order.
             *
             * This prevents concurrent stock updates from producing
             * an incorrect balance.
             */
            $sectionSizeIds = collect([
                $oldSectionSizeId,
                $newSectionSizeId,
            ])
                ->unique()
                ->sort()
                ->values();

            $sectionSizes = SectionSize::query()
                ->whereIn('id', $sectionSizeIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $oldSectionSize = $sectionSizes->get(
                $oldSectionSizeId
            );

            $newSectionSize = $sectionSizes->get(
                $newSectionSizeId
            );

            if (! $oldSectionSize || ! $newSectionSize) {
                abort(404);
            }

            /*
             * First reverse the existing transaction's stock effect.
             */
            $this->reverseStockMovement(
                $oldSectionSize,
                $oldType,
                $oldQuantity
            );

            /*
             * Then apply the new transaction's stock effect.
             */
            $this->applyStockMovement(
                $newSectionSize,
                $newType,
                $newQuantity
            );

            /*
             * Update the transaction itself.
             *
             * user_id is intentionally not changed. The transaction
             * remains attributed to the user who originally created it.
             */
            $rawMaterialTransaction->update($data);
        });

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        RawMaterialTransaction $rawMaterialTransaction
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $rawMaterialTransaction
        );

        DB::transaction(function () use (
            $rawMaterialTransaction
        ): void {
            $sectionSize = SectionSize::query()
                ->lockForUpdate()
                ->findOrFail(
                    $rawMaterialTransaction->section_size_id
                );

            /*
             * Reverse the stock effect before deleting the transaction.
             */
            $this->reverseStockMovement(
                $sectionSize,
                $rawMaterialTransaction->type,
                (int) $rawMaterialTransaction->quantity
            );

            $rawMaterialTransaction->delete();
        });

        return $this->successResponse('deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Destroy
    |--------------------------------------------------------------------------
    */

    public function bulkDestroy(
        BulkDestroyRequest $request
    ): RedirectResponse {
        Gate::authorize(
            'deleteAny',
            RawMaterialTransaction::class
        );

        DB::transaction(function () use ($request): void {
            $ids = $request->validated('ids');

            $transactions = RawMaterialTransaction::query()
                ->whereIn('id', $ids)
                ->get([
                    'id',
                    'section_size_id',
                    'type',
                    'quantity',
                ]);

            if ($transactions->isEmpty()) {
                return;
            }

            /*
             * Lock all affected section sizes in deterministic order.
             */
            $sectionSizeIds = $transactions
                ->pluck('section_size_id')
                ->unique()
                ->sort()
                ->values();

            $sectionSizes = SectionSize::query()
                ->whereIn('id', $sectionSizeIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /*
             * Reverse every transaction's stock movement.
             */
            foreach ($transactions as $transaction) {
                $sectionSize = $sectionSizes->get(
                    (int) $transaction->section_size_id
                );

                if (! $sectionSize) {
                    abort(404);
                }

                $this->reverseStockMovement(
                    $sectionSize,
                    $transaction->type,
                    (int) $transaction->quantity
                );
            }

            /*
             * Delete the transactions only after stock has been
             * successfully restored.
             */
            RawMaterialTransaction::query()
                ->whereIn(
                    'id',
                    $transactions->pluck('id')
                )
                ->delete();
        });

        return $this->successResponse('bulk_deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Form Options
    |--------------------------------------------------------------------------
    */

    private function formOptions(): array
    {
        return [
            'sectionSizes' => SectionSize::query()
                ->with('section:id,name')
                ->orderBy('section_id')
                ->orderBy('size')
                ->get([
                    'id',
                    'section_id',
                    'size',
                ])
                ->map(
                    fn (SectionSize $sectionSize): array => [
                        'value' => $sectionSize->id,
                        'label' => $sectionSize->section
                            ? "{$sectionSize->section->name} - {$sectionSize->size}"
                            : $sectionSize->size,
                    ]
                )
                ->values(),

            'transactionTypes' => RawMaterialTransactionType::options(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */

    /**
     * Apply a transaction's stock movement.
     *
     * Opening   => +
     * Purchase  => +
     * Outward   => -
     * Adjustment => +
     *
     * Adjustment is currently treated as a positive adjustment because
     * quantity is unsigned and the transaction does not have a direction
     * field yet.
     */
    private function applyStockMovement(
        SectionSize $sectionSize,
        RawMaterialTransactionType|string $type,
        int $quantity
    ): void {
        $type = $this->resolveTransactionType($type);

        if ($this->isStockIncreaseType($type)) {
            $sectionSize->increment(
                'stock_quantity',
                $quantity
            );

            return;
        }

        if ($type === RawMaterialTransactionType::OUTWARD) {
            $this->ensureStockAvailable(
                $sectionSize,
                $quantity
            );

            $sectionSize->decrement(
                'stock_quantity',
                $quantity
            );

            return;
        }

        throw ValidationException::withMessages([
            'type' => 'Invalid raw material transaction type.',
        ]);
    }

    /**
     * Reverse a transaction's previous stock movement.
     */
    private function reverseStockMovement(
        SectionSize $sectionSize,
        RawMaterialTransactionType|string $type,
        int $quantity
    ): void {
        $type = $this->resolveTransactionType($type);

        if ($this->isStockIncreaseType($type)) {
            /*
             * Opening/Purchase/Adjustment originally increased stock,
             * therefore deleting or replacing them decreases stock.
             */
            $this->ensureStockAvailable(
                $sectionSize,
                $quantity
            );

            $sectionSize->decrement(
                'stock_quantity',
                $quantity
            );

            return;
        }

        if ($type === RawMaterialTransactionType::OUTWARD) {
            /*
             * Outward originally decreased stock, therefore deleting
             * or replacing it returns the consumed material.
             */
            $sectionSize->increment(
                'stock_quantity',
                $quantity
            );

            return;
        }

        throw ValidationException::withMessages([
            'type' => 'Invalid raw material transaction type.',
        ]);
    }

    /**
     * Determine whether a transaction increases stock.
     */
    private function isStockIncreaseType(
        RawMaterialTransactionType $type
    ): bool {
        return in_array(
            $type,
            [
                RawMaterialTransactionType::OPENING,
                RawMaterialTransactionType::PURCHASE,
                RawMaterialTransactionType::ADJUSTMENT,
            ],
            true
        );
    }

    /**
     * Resolve the transaction type to the enum.
     */
    private function resolveTransactionType(
        RawMaterialTransactionType|string $type
    ): RawMaterialTransactionType {
        if ($type instanceof RawMaterialTransactionType) {
            return $type;
        }

        return RawMaterialTransactionType::from($type);
    }

    /**
     * Ensure sufficient raw material stock is available.
     */
    private function ensureStockAvailable(
        SectionSize $sectionSize,
        int $quantity
    ): void {
        $availableStock = (int) $sectionSize->stock_quantity;

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

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    private function successResponse(
        string $action,
    ): RedirectResponse {
        return ResponseHelper::success(
            message: FlashMessage::success(
                $action,
                self::MODULE_NAME,
            ),
            route: self::INDEX_ROUTE,
        );
    }
}
