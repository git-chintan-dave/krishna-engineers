<?php

namespace App\Services;

use App\Enums\RawMaterialTransactionType;
use App\Models\RawMaterialTransaction;
use App\Models\SectionSize;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RawMaterialTransactionService
{
    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        User  $user,
    ): RawMaterialTransaction
    {
        return DB::transaction(function () use ($data, $user) {
            $type = $this->resolveType($data['type']);
            $quantity = (int)$data['quantity'];

            $this->applyStockChanges([
                (int)$data['section_size_id'] => $this->movement(
                    $type,
                    $quantity,
                ),
            ]);

            return RawMaterialTransaction::query()->create([
                ...$data,
                'type' => $type,
                'quantity' => $quantity,
                'user_id' => $user->id,
            ]);
        }, attempts: 3);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    private function resolveType(
        RawMaterialTransactionType|string $type,
    ): RawMaterialTransactionType
    {
        return $type instanceof RawMaterialTransactionType
            ? $type
            : RawMaterialTransactionType::from($type);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    /**
     * Apply net stock changes after locking all affected section sizes.
     *
     * Positive values add stock.
     * Negative values remove stock.
     *
     * @param array<int, int> $changes
     */
    private function applyStockChanges(array $changes): void
    {
        $changes = collect($changes)
            ->mapWithKeys(
                fn(int|float $change, int|string $sectionSizeId): array => [
                    (int)$sectionSizeId => (int)$change,
                ],
            )
            ->filter(
                fn(int $change): bool => $change !== 0,
            )
            ->sortKeys();

        if ($changes->isEmpty()) {
            return;
        }

        /*
         * Lock all affected section sizes in a consistent order.
         */
        $sectionSizes = SectionSize::query()
            ->whereIn('id', $changes->keys())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($sectionSizes->count() !== $changes->count()) {
            abort(404, 'One or more section sizes were not found.');
        }

        /*
         * Validate every resulting balance before changing
         * any stock.
         */
        foreach ($changes as $sectionSizeId => $change) {
            $sectionSize = $sectionSizes->get($sectionSizeId);

            $currentStock = (int)$sectionSize->quantity;
            $newStock = $currentStock + $change;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Insufficient stock for section size %s. Available stock is %d.',
                        $sectionSize->size,
                        $currentStock,
                    ),
                ]);
            }
        }

        /*
         * Persist all balances while locks are held.
         */
        foreach ($changes as $sectionSizeId => $change) {
            $sectionSize = $sectionSizes->get($sectionSizeId);

            $sectionSize->quantity =
                (int)$sectionSize->quantity + $change;

            $sectionSize->save();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */

    private function movement(
        RawMaterialTransactionType $type,
        int                        $quantity,
    ): int
    {
        return match ($type) {
            RawMaterialTransactionType::OPENING,
            RawMaterialTransactionType::PURCHASE,
            RawMaterialTransactionType::ADJUSTMENT => $quantity,

            RawMaterialTransactionType::OUTWARD => -$quantity,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */

    public function update(
        RawMaterialTransaction $transaction,
        array                  $data,
    ): RawMaterialTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $transaction = RawMaterialTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            /*
             * Section size and transaction type are intentionally immutable.
             * Only quantity and remarks should be changed.
             */
            $newQuantity = (int)$data['quantity'];

            $oldType = $this->resolveType($transaction->type);
            $oldQuantity = (int)$transaction->quantity;

            $oldMovement = $this->movement(
                $oldType,
                $oldQuantity,
            );

            $newMovement = $this->movement(
                $oldType,
                $newQuantity,
            );

            $stockChange = $newMovement - $oldMovement;

            if ($stockChange !== 0) {
                $this->applyStockChanges([
                    (int)$transaction->section_size_id => $stockChange,
                ]);
            }

            $transaction->update([
                'quantity' => $newQuantity,
                'remarks' => $data['remarks'] ?? null,
            ]);

            return $transaction->refresh();
        }, attempts: 3);
    }

    /*
    |--------------------------------------------------------------------------
    | Movement
    |--------------------------------------------------------------------------
    */

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $transactions = RawMaterialTransaction::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($transactions->isEmpty()) {
                return 0;
            }

            $changes = [];

            foreach ($transactions as $transaction) {
                $sectionSizeId = (int)$transaction->section_size_id;

                $movement = $this->movement(
                    $this->resolveType($transaction->type),
                    (int)$transaction->quantity,
                );

                /*
                 * Reverse each transaction.
                 */
                $changes[$sectionSizeId] = (
                        $changes[$sectionSizeId] ?? 0
                    ) - $movement;
            }

            /*
             * applyStockChanges() validates all resulting balances
             * before changing anything.
             */
            $this->applyStockChanges($changes);

            return RawMaterialTransaction::query()
                ->whereIn('id', $transactions->modelKeys())
                ->delete();
        }, attempts: 3);
    }

    /*
    |--------------------------------------------------------------------------
    | Type
    |--------------------------------------------------------------------------
    */

    public function delete(
        RawMaterialTransaction $transaction,
    ): void
    {
        DB::transaction(function () use ($transaction): void {
            $transaction = RawMaterialTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $movement = $this->movement(
                $this->resolveType($transaction->type),
                (int)$transaction->quantity,
            );

            /*
             * Reverse the transaction movement.
             *
             * Opening/Purchase/Adjustment:
             *     +quantity -> -quantity
             *
             * Outward:
             *     -quantity -> +quantity
             */
            $this->applyStockChanges([
                (int)$transaction->section_size_id => -$movement,
            ]);

            $transaction->delete();
        }, attempts: 3);
    }
}
