<?php

namespace App\Services;

use App\Enums\ToolTransactionType;
use App\Models\Tool;
use App\Models\ToolTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ToolTransactionService
{
    public function opening(
        Tool $tool,
        int $quantity,
        User $user,
        ?string $remarks = null,
    ): ToolTransaction {
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($tool, $quantity, $user, $remarks) {
            $tool = $this->lockTool($tool->id);

            $this->changeStock($tool, $quantity);

            return $this->createTransaction($tool, [
                'type' => ToolTransactionType::OPENING,
                'quantity' => $quantity,
                'created_by_user_id' => $user->id,
                'remarks' => $remarks,
            ]);
        }, attempts: 3);
    }

    public function purchase(
        Tool $tool,
        int $quantity,
        User $user,
        ?string $remarks = null,
    ): ToolTransaction {
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($tool, $quantity, $user, $remarks) {
            $tool = $this->lockTool($tool->id);

            $this->changeStock($tool, $quantity);

            return $this->createTransaction($tool, [
                'type' => ToolTransactionType::PURCHASE,
                'quantity' => $quantity,
                'created_by_user_id' => $user->id,
                'remarks' => $remarks,
            ]);
        }, attempts: 3);
    }

    public function issue(
        Tool $tool,
        ?User $fromUser,
        User $toUser,
        int $quantity,
        User $createdBy,
        ?int $partId = null,
        ?int $setupId = null,
        ?bool $returnScrap = null,
        ?string $remarks = null,
    ): ToolTransaction {
        $this->ensurePositiveQuantity($quantity);

        if ($fromUser && $fromUser->is($toUser)) {
            throw ValidationException::withMessages([
                'to_user_id' => 'The receiving user must be different from the issuing user.',
            ]);
        }

        if ($this->isCentralStockIssuer($toUser)) {
            throw ValidationException::withMessages([
                'to_user_id' => 'Central stock cannot be selected as the receiving user.',
            ]);
        }

        return DB::transaction(function () use (
            $tool,
            $fromUser,
            $toUser,
            $quantity,
            $createdBy,
            $partId,
            $setupId,
            $returnScrap,
            $remarks,
        ) {
            $tool = $this->lockTool($tool->id);

            if ($fromUser && ! $this->isCentralStockIssuer($fromUser)) {
                $this->ensureUserHasCustody($tool, $fromUser, $quantity);
            } else {
                $this->ensureCentralStock($tool, $quantity);

                // Central stock is physically leaving the store.
                $this->changeStock($tool, -$quantity);
            }

            return $this->createTransaction($tool, [
                'type' => ToolTransactionType::ISSUE,
                'quantity' => $quantity,
                'from_user_id' => $fromUser?->id,
                'to_user_id' => $toUser->id,
                'part_id' => $partId,
                'setup_id' => $setupId,
                'return_scrap' => $returnScrap,
                'created_by_user_id' => $createdBy->id,
                'remarks' => $remarks,
            ]);
        }, attempts: 3);
    }

    public function adjustment(
        Tool $tool,
        int $quantity,
        User $createdBy,
        ?string $remarks = null,
    ): ToolTransaction {
        if ($quantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment quantity cannot be zero.',
            ]);
        }

        return DB::transaction(function () use ($tool, $quantity, $createdBy, $remarks) {
            $tool = $this->lockTool($tool->id);

            $this->changeStock($tool, $quantity);

            return $this->createTransaction($tool, [
                'type' => ToolTransactionType::ADJUSTMENT,
                'quantity' => $quantity,
                'created_by_user_id' => $createdBy->id,
                'remarks' => $remarks,
            ]);
        }, attempts: 3);
    }

    public function update(
        ToolTransaction $transaction,
        array $data,
        User $user,
    ): ToolTransaction {
        return DB::transaction(function () use ($transaction, $data) {
            $transaction = ToolTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $tool = $this->lockTool($transaction->tool_id);

            $oldQuantity = (int) $transaction->quantity;
            $newQuantity = (int) $data['quantity'];

            if ($transaction->type === ToolTransactionType::ADJUSTMENT) {
                if ($newQuantity === 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Adjustment quantity cannot be zero.',
                    ]);
                }

                $this->changeStock(
                    $tool,
                    $newQuantity - $oldQuantity,
                );
            } elseif (in_array($transaction->type, [
                ToolTransactionType::OPENING,
                ToolTransactionType::PURCHASE,
            ], true)) {
                $this->ensurePositiveQuantity($newQuantity);

                $this->changeStock(
                    $tool,
                    $newQuantity - $oldQuantity,
                );
            } else {
                $this->ensurePositiveQuantity($newQuantity);

                $this->validateIssueQuantityChange(
                    $transaction,
                    $tool,
                    $newQuantity,
                );
            }

            $transaction->update([
                'quantity' => $newQuantity,
                'remarks' => $data['remarks'] ?? null,
            ]);

            return $transaction->refresh();
        }, attempts: 3);
    }

    public function delete(
        ToolTransaction $transaction,
        User $user,
    ): void {
        DB::transaction(function () use ($transaction) {
            $transaction = ToolTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $tool = $this->lockTool($transaction->tool_id);

            $this->reverseTransactionEffect($transaction, $tool);

            $transaction->delete();
        }, attempts: 3);
    }

    public function bulkDelete(
        Collection $transactions,
        User $user,
    ): void {
        DB::transaction(function () use ($transactions) {
            $ids = $transactions->pluck('id')->unique()->values();

            if ($ids->isEmpty()) {
                return;
            }

            $lockedTransactions = ToolTransaction::query()
                ->whereIn('id', $ids)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $toolIds = $lockedTransactions
                ->pluck('tool_id')
                ->unique()
                ->sort()
                ->values();

            $tools = Tool::query()
                ->whereIn('id', $toolIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lockedTransactions as $transaction) {
                $tool = $tools->get($transaction->tool_id);

                if (! $tool) {
                    throw ValidationException::withMessages([
                        'transactions' => 'One or more tools could not be found.',
                    ]);
                }

                $this->reverseTransactionEffect($transaction, $tool);

                $transaction->delete();
            }
        }, attempts: 3);
    }

    public function getUserCustody(
        Tool $tool,
        User $user,
        ?int $excludeTransactionId = null,
    ): int {
        if ($this->isCentralStockIssuer($user)) {
            return $this->getSuperAdminCustody($tool);
        }

        $query = $tool->transactions()
            ->where('type', ToolTransactionType::ISSUE->value);

        if ($excludeTransactionId !== null) {
            $query->where('id', '!=', $excludeTransactionId);
        }

        $received = (int) (clone $query)
            ->where('to_user_id', $user->id)
            ->sum('quantity');

        $transferred = (int) (clone $query)
            ->where('from_user_id', $user->id)
            ->sum('quantity');

        return $received - $transferred;
    }

    public function getSuperAdminCustody(Tool $tool): int
    {
        return max(0, (int) $tool->quantity);
    }

    private function validateIssueQuantityChange(
        ToolTransaction $transaction,
        Tool $tool,
        int $quantity,
    ): void {
        $fromUserId = $transaction->from_user_id;

        if ($fromUserId !== null) {
            $fromUser = User::query()->findOrFail($fromUserId);

            if (! $this->isCentralStockIssuer($fromUser)) {
                $custody = $this->getUserCustody(
                    $tool,
                    $fromUser,
                    $transaction->id,
                );

                if ($custody < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'The issuing user does not have enough tools in custody.',
                    ]);
                }

                return;
            }
        }

        /*
         * The current tool quantity already excludes the original issue.
         * Add the old quantity back before validating the new quantity.
         */
        $availableCentralStock = (int) $tool->quantity
            + (int) $transaction->quantity;

        if ($availableCentralStock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'There is not enough available tool stock.',
            ]);
        }

        $this->changeStock(
            $tool,
            (int) $transaction->quantity - $quantity,
        );
    }

    private function reverseTransactionEffect(
        ToolTransaction $transaction,
        Tool $tool,
    ): void {
        match ($transaction->type) {
            ToolTransactionType::OPENING,
            ToolTransactionType::PURCHASE,
            ToolTransactionType::ADJUSTMENT => $this->changeStock(
                $tool,
                -(int) $transaction->quantity,
            ),

            ToolTransactionType::ISSUE => $this->reverseIssue(
                $transaction,
                $tool,
            ),
        };
    }

    private function reverseIssue(
        ToolTransaction $transaction,
        Tool $tool,
    ): void {
        /*
         * Only an issue from central stock decreases tools.quantity.
         *
         * Employee-to-employee transfers do not affect central stock.
         */
        if (
            $transaction->from_user_id === null
            || $this->isCentralStockIssuer(
                User::query()->findOrFail($transaction->from_user_id),
            )
        ) {
            $this->changeStock(
                $tool,
                (int) $transaction->quantity,
            );
        }
    }

    private function changeStock(Tool $tool, int $change): void
    {
        $newQuantity = (int) $tool->quantity + $change;

        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'This transaction would result in negative available tool stock.',
            ]);
        }

        $tool->update([
            'quantity' => $newQuantity,
        ]);
    }

    private function ensureCentralStock(
        Tool $tool,
        int $quantity,
    ): void {
        if ($this->getSuperAdminCustody($tool) < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'There is not enough available tool stock.',
            ]);
        }
    }

    private function ensureUserHasCustody(
        Tool $tool,
        User $user,
        int $quantity,
    ): void {
        if ($this->getUserCustody($tool, $user) < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'The issuing user does not have enough tools in custody.',
            ]);
        }
    }

    private function ensurePositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }
    }

    private function isCentralStockIssuer(User $user): bool
    {
        return $user->getRawOriginal('role') === 'super_admin';
    }

    private function createTransaction(
        Tool $tool,
        array $data,
    ): ToolTransaction {
        return $tool->transactions()->create($data);
    }

    private function lockTool(int $toolId): Tool
    {
        return Tool::query()
            ->lockForUpdate()
            ->findOrFail($toolId);
    }
}
