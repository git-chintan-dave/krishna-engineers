<?php

namespace App\Services;

use App\Enums\ToolTransactionType;
use App\Enums\UserRole;
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
        ?string $remarks = null
    ): ToolTransaction {
        $this->ensureSuperAdmin($user);
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($tool, $quantity, $user, $remarks) {
            $tool = $this->lockTool($tool);

            $transaction = $tool->transactions()->create([
                'type' => ToolTransactionType::OPENING,
                'quantity' => $quantity,
                'created_by_user_id' => $user->id,
                'remarks' => $remarks,
            ]);

            $tool->increment('total_quantity', $quantity);

            return $transaction->refresh();
        });
    }

    public function purchase(
        Tool $tool,
        int $quantity,
        User $user,
        ?string $remarks = null
    ): ToolTransaction {
        $this->ensureSuperAdmin($user);
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($tool, $quantity, $user, $remarks) {
            $tool = $this->lockTool($tool);

            $transaction = $tool->transactions()->create([
                'type' => ToolTransactionType::PURCHASE,
                'quantity' => $quantity,
                'created_by_user_id' => $user->id,
                'remarks' => $remarks,
            ]);

            $tool->increment('total_quantity', $quantity);

            return $transaction->refresh();
        });
    }

    public function issue(
        Tool $tool,
        User $fromUser,
        User $toUser,
        int $quantity,
        User $createdBy,
        ?int $partId = null,
        ?int $setupId = null,
        ?string $remarks = null
    ): ToolTransaction {
        $this->ensureCanCreateNormalTransaction($createdBy);
        $this->ensureDifferentUsers($fromUser, $toUser);
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use (
            $tool,
            $fromUser,
            $toUser,
            $quantity,
            $createdBy,
            $partId,
            $setupId,
            $remarks
        ) {
            $tool = $this->lockTool($tool);

            $this->ensureUserHasCustody(
                $tool,
                $fromUser,
                $quantity
            );

            $transaction = $tool->transactions()->create([
                'type' => ToolTransactionType::ISSUE,
                'quantity' => $quantity,
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser->id,
                'part_id' => $partId,
                'setup_id' => $setupId,
                'created_by_user_id' => $createdBy->id,
                'remarks' => $remarks,
            ]);

            return $transaction->refresh();
        });
    }

    public function return(
        Tool $tool,
        User $fromUser,
        int $quantity,
        User $createdBy,
        ?int $partId = null,
        ?int $setupId = null,
        ?bool $returnScrap = null,
        ?string $remarks = null
    ): ToolTransaction {
        $this->ensureCanCreateNormalTransaction($createdBy);
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use (
            $tool,
            $fromUser,
            $quantity,
            $createdBy,
            $partId,
            $setupId,
            $returnScrap,
            $remarks
        ) {
            $tool = $this->lockTool($tool);

            $this->ensureUserHasCustody(
                $tool,
                $fromUser,
                $quantity
            );

            $transaction = $tool->transactions()->create([
                'type' => ToolTransactionType::RETURN,
                'quantity' => $quantity,
                'from_user_id' => $fromUser->id,
                'to_user_id' => null,
                'part_id' => $partId,
                'setup_id' => $setupId,
                'return_scrap' => $returnScrap,
                'created_by_user_id' => $createdBy->id,
                'remarks' => $remarks,
            ]);

            return $transaction->refresh();
        });
    }

    public function adjustment(
        Tool $tool,
        int $quantity,
        User $createdBy,
        ?string $remarks = null
    ): ToolTransaction {
        $this->ensureSuperAdmin($createdBy);

        if ($quantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment quantity cannot be zero.',
            ]);
        }

        return DB::transaction(function () use (
            $tool,
            $quantity,
            $createdBy,
            $remarks
        ) {
            $tool = $this->lockTool($tool);

            $newTotal = $tool->total_quantity + $quantity;

            if ($newTotal < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Adjustment would result in negative total quantity.',
                ]);
            }

            $transaction = $tool->transactions()->create([
                'type' => ToolTransactionType::ADJUSTMENT,
                'quantity' => abs($quantity),
                'created_by_user_id' => $createdBy->id,
                'remarks' => $remarks,
            ]);

            $tool->update([
                'total_quantity' => $newTotal,
            ]);

            return $transaction->refresh();
        });
    }

    /**
     * Update an existing transaction.
     *
     * The old transaction effect is reversed first and the new
     * transaction effect is then applied.
     */
    public function update(
        ToolTransaction $transaction,
        array $data,
        User $user
    ): ToolTransaction {
        $this->ensureSuperAdmin($user);

        return DB::transaction(function () use (
            $transaction,
            $data,
            $user
        ) {
            $transaction = ToolTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $oldTool = $this->lockToolById($transaction->tool_id);

            $this->reverseTransactionEffect($transaction, $oldTool);

            $transaction->delete();

            $newTool = $this->lockToolById((int) $data['tool_id']);

            $newTransaction = $this->applyTransaction(
                tool: $newTool,
                data: $data,
                createdBy: $user,
            );

            return $newTransaction->refresh();
        });
    }

    /**
     * Delete a transaction and reverse its inventory/custody effect.
     */
    public function delete(
        ToolTransaction $transaction,
        User $user
    ): void {
        $this->ensureSuperAdmin($user);

        DB::transaction(function () use ($transaction) {
            $transaction = ToolTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $tool = $this->lockToolById($transaction->tool_id);

            $this->reverseTransactionEffect($transaction, $tool);

            $transaction->delete();
        });
    }

    /**
     * Delete multiple transactions and reverse their effects.
     *
     * Transactions are processed in reverse creation order so that
     * dependent custody movements are safely rolled back.
     */
    public function bulkDelete(
        Collection $transactions,
        User $user
    ): void {
        $this->ensureSuperAdmin($user);

        DB::transaction(function () use ($transactions) {
            $transactionIds = $transactions
                ->pluck('id')
                ->filter()
                ->values();

            if ($transactionIds->isEmpty()) {
                return;
            }

            $lockedTransactions = ToolTransaction::query()
                ->whereIn('id', $transactionIds)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $toolIds = $lockedTransactions
                ->pluck('tool_id')
                ->unique()
                ->values();

            $tools = Tool::query()
                ->whereIn('id', $toolIds)
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

                $this->reverseTransactionEffect(
                    $transaction,
                    $tool
                );

                $transaction->delete();
            }
        });
    }

    public function getUserCustody(
        Tool $tool,
        User $user
    ): int {
        $issued = (int) $tool->transactions()
            ->where('type', ToolTransactionType::ISSUE->value)
            ->where('to_user_id', $user->id)
            ->sum('quantity');

        $returned = (int) $tool->transactions()
            ->where('type', ToolTransactionType::RETURN->value)
            ->where('from_user_id', $user->id)
            ->sum('quantity');

        return max(0, $issued - $returned);
    }

    public function getSuperAdminCustody(Tool $tool): int
    {
        $issued = (int) $tool->transactions()
            ->where('type', ToolTransactionType::ISSUE->value)
            ->sum('quantity');

        $returned = (int) $tool->transactions()
            ->where('type', ToolTransactionType::RETURN->value)
            ->sum('quantity');

        return max(
            0,
            (int) $tool->total_quantity - $issued + $returned
        );
    }

    private function applyTransaction(
        Tool $tool,
        array $data,
        User $createdBy
    ): ToolTransaction {
        $type = ToolTransactionType::from($data['type']);
        $quantity = (int) $data['quantity'];

        return match ($type) {
            ToolTransactionType::OPENING => $this->applyOpeningTransaction(
                $tool,
                $quantity,
                $createdBy,
                $data['remarks'] ?? null,
            ),

            ToolTransactionType::PURCHASE => $this->applyPurchaseTransaction(
                $tool,
                $quantity,
                $createdBy,
                $data['remarks'] ?? null,
            ),

            ToolTransactionType::ISSUE => $this->applyIssueTransaction(
                $tool,
                $data,
                $createdBy,
            ),

            ToolTransactionType::RETURN => $this->applyReturnTransaction(
                $tool,
                $data,
                $createdBy,
            ),

            ToolTransactionType::ADJUSTMENT => $this->applyAdjustmentTransaction(
                $tool,
                $data,
                $createdBy,
            ),
        };
    }

    private function applyOpeningTransaction(
        Tool $tool,
        int $quantity,
        User $createdBy,
        ?string $remarks
    ): ToolTransaction {
        $this->ensurePositiveQuantity($quantity);
        $this->ensureSuperAdmin($createdBy);

        $transaction = $tool->transactions()->create([
            'type' => ToolTransactionType::OPENING,
            'quantity' => $quantity,
            'created_by_user_id' => $createdBy->id,
            'remarks' => $remarks,
        ]);

        $tool->increment('total_quantity', $quantity);

        return $transaction;
    }

    private function applyPurchaseTransaction(
        Tool $tool,
        int $quantity,
        User $createdBy,
        ?string $remarks
    ): ToolTransaction {
        $this->ensurePositiveQuantity($quantity);
        $this->ensureSuperAdmin($createdBy);

        $transaction = $tool->transactions()->create([
            'type' => ToolTransactionType::PURCHASE,
            'quantity' => $quantity,
            'created_by_user_id' => $createdBy->id,
            'remarks' => $remarks,
        ]);

        $tool->increment('total_quantity', $quantity);

        return $transaction;
    }

    private function applyIssueTransaction(
        Tool $tool,
        array $data,
        User $createdBy
    ): ToolTransaction {
        $this->ensureCanCreateNormalTransaction($createdBy);

        $fromUser = User::query()->findOrFail((int) $data['from_user_id']);
        $toUser = User::query()->findOrFail((int) $data['to_user_id']);

        $this->ensureDifferentUsers($fromUser, $toUser);

        $quantity = (int) $data['quantity'];
        $this->ensurePositiveQuantity($quantity);

        $this->ensureUserHasCustody(
            $tool,
            $fromUser,
            $quantity
        );

        return $tool->transactions()->create([
            'type' => ToolTransactionType::ISSUE,
            'quantity' => $quantity,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'part_id' => $data['part_id'] ?? null,
            'setup_id' => $data['setup_id'] ?? null,
            'created_by_user_id' => $createdBy->id,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    private function applyReturnTransaction(
        Tool $tool,
        array $data,
        User $createdBy
    ): ToolTransaction {
        $this->ensureCanCreateNormalTransaction($createdBy);

        $fromUser = User::query()->findOrFail((int) $data['from_user_id']);

        $quantity = (int) $data['quantity'];
        $this->ensurePositiveQuantity($quantity);

        $this->ensureUserHasCustody(
            $tool,
            $fromUser,
            $quantity
        );

        return $tool->transactions()->create([
            'type' => ToolTransactionType::RETURN,
            'quantity' => $quantity,
            'from_user_id' => $fromUser->id,
            'to_user_id' => null,
            'part_id' => $data['part_id'] ?? null,
            'setup_id' => $data['setup_id'] ?? null,
            'return_scrap' => $data['return_scrap'] ?? null,
            'created_by_user_id' => $createdBy->id,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    private function applyAdjustmentTransaction(
        Tool $tool,
        array $data,
        User $createdBy
    ): ToolTransaction {
        $this->ensureSuperAdmin($createdBy);

        $quantity = (int) $data['quantity'];

        if ($quantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment quantity cannot be zero.',
            ]);
        }

        $newTotal = $tool->total_quantity + $quantity;

        if ($newTotal < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment would result in negative total quantity.',
            ]);
        }

        $transaction = $tool->transactions()->create([
            'type' => ToolTransactionType::ADJUSTMENT,
            'quantity' => abs($quantity),
            'created_by_user_id' => $createdBy->id,
            'remarks' => $data['remarks'] ?? null,
        ]);

        $tool->update([
            'total_quantity' => $newTotal,
        ]);

        return $transaction;
    }

    private function reverseTransactionEffect(
        ToolTransaction $transaction,
        Tool $tool
    ): void {
        $quantity = (int) $transaction->quantity;

        match ($transaction->type) {
            ToolTransactionType::OPENING,
            ToolTransactionType::PURCHASE => $this->reverseStockIncrease(
                $tool,
                $quantity
            ),

            ToolTransactionType::ADJUSTMENT => $this->reverseAdjustment(
                $tool,
                $transaction
            ),

            ToolTransactionType::ISSUE,
            ToolTransactionType::RETURN => null,
        };
    }

    private function reverseStockIncrease(
        Tool $tool,
        int $quantity
    ): void {
        $newTotal = (int) $tool->total_quantity - $quantity;

        if ($newTotal < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Transaction cannot be deleted because it would result in negative total quantity.',
            ]);
        }

        $tool->update([
            'total_quantity' => $newTotal,
        ]);
    }

    private function reverseAdjustment(
        Tool $tool,
        ToolTransaction $transaction
    ): void {
        /*
         * Adjustment direction is stored in remarks/history currently
         * only through the resulting total. Because the transaction
         * stores abs(quantity), an adjustment's original direction
         * cannot be reconstructed safely from this record alone.
         *
         * Therefore adjustment transactions should not be edited/deleted
         * until a signed adjustment field is added to the schema.
         */
        throw ValidationException::withMessages([
            'transaction' => 'Adjustment transactions cannot currently be edited or deleted because their original direction is not stored.',
        ]);
    }

    private function ensureUserHasCustody(
        Tool $tool,
        User $user,
        int $quantity
    ): void {
        $custody = $this->getUserCustody($tool, $user);

        if ($custody < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'User does not have enough tool quantity in custody.',
            ]);
        }
    }

    private function ensurePositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }
    }

    private function ensureDifferentUsers(
        User $fromUser,
        User $toUser
    ): void {
        if ($fromUser->id === $toUser->id) {
            throw ValidationException::withMessages([
                'to_user_id' => 'Tool cannot be issued to the same user.',
            ]);
        }
    }

    private function ensureCanCreateNormalTransaction(User $user): void
    {
        if (! in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::SUPERVISOR,
        ], true)) {
            throw ValidationException::withMessages([
                'user' => 'Only Super Admin or Supervisor can create tool issue/return transactions.',
            ]);
        }
    }

    private function ensureSuperAdmin(User $user): void
    {
        if ($user->role !== UserRole::SUPER_ADMIN) {
            throw ValidationException::withMessages([
                'user' => 'Only Super Admin can perform this operation.',
            ]);
        }
    }

    private function lockTool(Tool $tool): Tool
    {
        return $this->lockToolById($tool->id);
    }

    private function lockToolById(int $toolId): Tool
    {
        return Tool::query()
            ->lockForUpdate()
            ->findOrFail($toolId);
    }
}
