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
    public function opening(
        SectionSize $sectionSize,
        float $quantity,
        User $user,
        ?string $remarks = null
    ): RawMaterialTransaction {
        return $this->create(
            sectionSize: $sectionSize,
            type: RawMaterialTransactionType::OPENING,
            quantity: $quantity,
            user: $user,
            remarks: $remarks,
        );
    }

    public function purchase(
        SectionSize $sectionSize,
        float $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $remarks = null
    ): RawMaterialTransaction {
        return $this->create(
            sectionSize: $sectionSize,
            type: RawMaterialTransactionType::PURCHASE,
            quantity: $quantity,
            user: $user,
            referenceType: $referenceType,
            referenceId: $referenceId,
            remarks: $remarks,
        );
    }

    public function outward(
        SectionSize $sectionSize,
        float $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $remarks = null
    ): RawMaterialTransaction {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Outward quantity must be greater than zero.',
            ]);
        }

        if ($sectionSize->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient raw material stock.',
            ]);
        }

        return $this->create(
            sectionSize: $sectionSize,
            type: RawMaterialTransactionType::OUTWARD,
            quantity: $quantity,
            user: $user,
            referenceType: $referenceType,
            referenceId: $referenceId,
            remarks: $remarks,
        );
    }

    public function adjustment(
        SectionSize $sectionSize,
        float $quantity,
        User $user,
        ?string $remarks = null
    ): RawMaterialTransaction {
        if ($quantity === 0.0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment quantity cannot be zero.',
            ]);
        }

        if ($sectionSize->stock_quantity + $quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment would result in negative stock.',
            ]);
        }

        return $this->create(
            sectionSize: $sectionSize,
            type: RawMaterialTransactionType::ADJUSTMENT,
            quantity: $quantity,
            user: $user,
            remarks: $remarks,
        );
    }

    private function create(
        SectionSize $sectionSize,
        RawMaterialTransactionType $type,
        float $quantity,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $remarks = null
    ): RawMaterialTransaction {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use (
            $sectionSize,
            $type,
            $quantity,
            $user,
            $referenceType,
            $referenceId,
            $remarks
        ) {
            $sectionSize = SectionSize::query()
                ->lockForUpdate()
                ->findOrFail($sectionSize->id);

            $stockChange = match ($type) {
                RawMaterialTransactionType::OPENING,
                RawMaterialTransactionType::PURCHASE => $quantity,

                RawMaterialTransactionType::OUTWARD => -$quantity,

                RawMaterialTransactionType::ADJUSTMENT => $quantity,
            };

            $newStock = $sectionSize->stock_quantity + $stockChange;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Transaction would result in negative stock.',
                ]);
            }

            $transaction = $sectionSize->rawMaterialTransactions()->create([
                'type' => $type,
                'quantity' => $quantity,
                'user_id' => $user->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'remarks' => $remarks,
            ]);

            $sectionSize->update([
                'stock_quantity' => $newStock,
            ]);

            return $transaction;
        });
    }
}
