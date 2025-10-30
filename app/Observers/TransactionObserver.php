<?php

namespace App\Observers;

use App\Models\MasterData\Transaction;
use App\Models\MasterData\Account;
use App\Models\MasterData\Budget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    private $processedTransactions = [];

    /**
     * Handle the Transaction "creating" event.
     */
    public function creating(Transaction $transaction): void
    {
        // 🎯 VALIDASI SEBELUM CREATE
        $this->validateTransaction($transaction);
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        // 🎯 PROTECTION: Cek double execution
        if ($this->isAlreadyProcessed($transaction->id, 'created')) {
            Log::warning("🛑 DOUBLE EXECUTION PREVENTED - Transaction ID: " . $transaction->id);
            return;
        }

        DB::transaction(function () use ($transaction) {
            try {
                Log::info("🎯 TRANSACTION CREATED - Starting processing", [
                    'transaction_id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'account_id' => $transaction->account_id,
                    'category_id' => $transaction->category_id
                ]);

                $this->updateAccountBalance($transaction, 'create');
                
                if ($transaction->type === 'expense') {
                    $this->updateBudget($transaction, 'create');
                }

                $this->markAsProcessed($transaction->id, 'created');

                Log::info("✅ TRANSACTION CREATED - Completed successfully", [
                    'transaction_id' => $transaction->id,
                    'final_balance' => Account::find($transaction->account_id)->current_balance ?? 'unknown'
                ]);

            } catch (\Exception $e) {
                Log::error("❌ TRANSACTION CREATED - Error", [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle the Transaction "updating" event.
     */
    public function updating(Transaction $transaction): void
    {
        // 🎯 VALIDASI SEBELUM UPDATE
        $this->validateTransaction($transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // 🎯 PROTECTION: Cek double execution
        if ($this->isAlreadyProcessed($transaction->id, 'updated')) {
            Log::warning("🛑 DOUBLE EXECUTION PREVENTED - Transaction ID: " . $transaction->id);
            return;
        }

        // Hanya proses jika field yang relevan berubah
        $relevantChanges = array_intersect(
            ['type', 'account_id', 'category_id', 'amount'],
            array_keys($transaction->getDirty())
        );

        if (empty($relevantChanges)) {
            Log::info("🔸 TRANSACTION UPDATED - No relevant changes, skipping", [
                'transaction_id' => $transaction->id,
                'changes' => $transaction->getDirty()
            ]);
            return;
        }

        DB::transaction(function () use ($transaction, $relevantChanges) {
            try {
                Log::info("🎯 TRANSACTION UPDATED - Starting processing", [
                    'transaction_id' => $transaction->id,
                    'relevant_changes' => $relevantChanges,
                    'changes' => $transaction->getDirty()
                ]);

                $this->handleTransactionUpdate($transaction);

                $this->markAsProcessed($transaction->id, 'updated');

                Log::info("✅ TRANSACTION UPDATED - Completed successfully", [
                    'transaction_id' => $transaction->id
                ]);

            } catch (\Exception $e) {
                Log::error("❌ TRANSACTION UPDATED - Error", [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        // 🎯 PROTECTION: Cek double execution
        if ($this->isAlreadyProcessed($transaction->id, 'deleted')) {
            Log::warning("🛑 DOUBLE EXECUTION PREVENTED - Transaction ID: " . $transaction->id);
            return;
        }

        DB::transaction(function () use ($transaction) {
            try {
                Log::info("🎯 TRANSACTION DELETED - Starting reversal", [
                    'transaction_id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount
                ]);

                $this->updateAccountBalance($transaction, 'delete');
                
                if ($transaction->type === 'expense') {
                    $this->updateBudget($transaction, 'delete');
                }

                $this->markAsProcessed($transaction->id, 'deleted');

                Log::info("✅ TRANSACTION DELETED - Completed successfully", [
                    'transaction_id' => $transaction->id
                ]);

            } catch (\Exception $e) {
                Log::error("❌ TRANSACTION DELETED - Error", [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        // Handle restore jika menggunakan soft delete
        DB::transaction(function () use ($transaction) {
            Log::info("🔄 TRANSACTION RESTORED - Starting restoration", [
                'transaction_id' => $transaction->id
            ]);

            $this->updateAccountBalance($transaction, 'create');
            
            if ($transaction->type === 'expense') {
                $this->updateBudget($transaction, 'create');
            }
        });
    }

    /**
     * 🎯 DOUBLE EXECUTION PROTECTION METHODS
     */

    private function isAlreadyProcessed($transactionId, $event): bool
    {
        $key = "{$transactionId}_{$event}";
        return in_array($key, $this->processedTransactions);
    }

    private function markAsProcessed($transactionId, $event): void
    {
        $key = "{$transactionId}_{$event}";
        $this->processedTransactions[] = $key;
        
        Log::debug("🔒 MARKED AS PROCESSED", [
            'transaction_id' => $transactionId,
            'event' => $event,
            'processed_count' => count($this->processedTransactions)
        ]);
    }

    /**
     * Validate transaction business rules
     */
    private function validateTransaction(Transaction $transaction): void
    {
        // Validasi amount positif
        if ($transaction->amount <= 0) {
            throw new \Exception("Amount transaksi harus lebih besar dari 0.");
        }

        // Untuk expense, pastikan account balance mencukupi
        if ($transaction->type === 'expense') {
            $account = Account::find($transaction->account_id);
            
            if (!$account) {
                throw new \Exception("Akun tidak ditemukan.");
            }
            
            if (!$this->hasSufficientBalance($account, $transaction->amount)) {
                throw new \Exception("Saldo akun {$account->name} tidak mencukupi untuk transaksi pengeluaran ini. Saldo: Rp " . number_format($account->current_balance, 0, ',', '.') . ", Dibutuhkan: Rp " . number_format($transaction->amount, 0, ',', '.'));
            }
        }

        // // Validasi kategori sesuai dengan tipe transaksi
        // if ($transaction->category) {
        //     $expectedBudgetType = $transaction->type === 'savings' ? 'savings' : $transaction->type;
        //     if ($transaction->category->budget_type !== $expectedBudgetType) {
        //         throw new \Exception("Kategori {$transaction->category->name} tidak sesuai untuk transaksi tipe {$transaction->type}.");
        //     }
        // }
    }

    /**
     * Check if account has sufficient balance
     */
    private function hasSufficientBalance(Account $account, float $amount): bool
    {
        return $account->current_balance >= $amount;
    }

    /**
     * Update account balance based on transaction
     */
    private function updateAccountBalance(Transaction $transaction, string $action): void
    {
        $account = Account::find($transaction->account_id);
        
        if (!$account) {
            Log::error("❌ ACCOUNT NOT FOUND", [
                'account_id' => $transaction->account_id,
                'transaction_id' => $transaction->id
            ]);
            return;
        }

        $amount = $transaction->amount;
        $oldBalance = $account->current_balance;

        Log::info("💰 ACCOUNT BALANCE UPDATE START", [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'transaction_id' => $transaction->id,
            'action' => $action,
            'type' => $transaction->type,
            'amount' => $amount,
            'old_balance' => $oldBalance
        ]);

        // 🎯 GUNAKAN increment/decrement UNTUK ATOMIC UPDATE
        switch ($transaction->type) {
            case 'income':
            case 'savings':
                if ($action === 'create') {
                    $account->increment('current_balance', $amount);
                } elseif ($action === 'delete') {
                    $account->decrement('current_balance', $amount);
                }
                break;
                
            case 'expense':
                if ($action === 'create') {
                    $account->decrement('current_balance', $amount);
                } elseif ($action === 'delete') {
                    $account->increment('current_balance', $amount);
                }
                break;
        }

        // Refresh untuk mendapatkan nilai terbaru
        $account->refresh();

        Log::info("💰 ACCOUNT BALANCE UPDATE COMPLETE", [
            'account_id' => $account->id,
            'new_balance' => $account->current_balance,
            'balance_change' => $account->current_balance - $oldBalance,
            'expected_change' => $this->calculateExpectedChange($transaction->type, $amount, $action),
            'calculation_correct' => ($account->current_balance - $oldBalance) === $this->calculateExpectedChange($transaction->type, $amount, $action)
        ]);
    }

    /**
     * Calculate expected balance change for verification
     */
    private function calculateExpectedChange($type, $amount, $action): float
    {
        $multiplier = 1;
        
        if ($type === 'expense') {
            $multiplier = -1;
        }
        
        if ($action === 'delete') {
            $multiplier *= -1;
        }
        
        return $amount * $multiplier;
    }

    /**
     * Update budget based on expense transaction
     */
    private function updateBudget(Transaction $transaction, string $action): void
    {
        $budget = Budget::where('category_id', $transaction->category_id)
            ->active()
            ->first();

        if (!$budget) {
            Log::warning("⚠️ NO ACTIVE BUDGET FOUND", [
                'category_id' => $transaction->category_id,
                'category_name' => $transaction->category->name ?? 'Unknown',
                'transaction_id' => $transaction->id
            ]);
            return;
        }

        $amount = $transaction->amount;
        $oldSpent = $budget->spent_amount;

        Log::info("📊 BUDGET UPDATE START", [
            'budget_id' => $budget->id,
            'category_name' => $budget->category->name ?? 'Unknown',
            'transaction_id' => $transaction->id,
            'action' => $action,
            'amount' => $amount,
            'old_spent' => $oldSpent,
            'old_remaining' => $budget->remaining_amount
        ]);

        // 🎯 GUNAKAN increment/decrement UNTUK ATOMIC UPDATE
        if ($action === 'create') {
            $budget->increment('spent_amount', $amount);
        } elseif ($action === 'delete') {
            $budget->decrement('spent_amount', $amount);
            
            // Pastikan tidak negatif
            if ($budget->spent_amount < 0) {
                $budget->update(['spent_amount' => 0]);
            }
        }

        // Refresh untuk mendapatkan nilai terbaru
        $budget->refresh();

        Log::info("📊 BUDGET UPDATE COMPLETE", [
            'budget_id' => $budget->id,
            'new_spent' => $budget->spent_amount,
            'new_remaining' => $budget->remaining_amount,
            'usage_percentage' => round($budget->usage_percentage, 2) . '%',
            'budget_status' => $budget->budget_status
        ]);
    }

    /**
     * Handle complex update scenario
     */
    private function handleTransactionUpdate(Transaction $transaction): void
    {
        $originalType = $transaction->getOriginal('type');
        $originalAccountId = $transaction->getOriginal('account_id');
        $originalCategoryId = $transaction->getOriginal('category_id');
        $originalAmount = $transaction->getOriginal('amount');

        Log::info("🔄 COMPLEX UPDATE - Reversing old effects", [
            'transaction_id' => $transaction->id,
            'original_type' => $originalType,
            'new_type' => $transaction->type,
            'original_amount' => $originalAmount,
            'new_amount' => $transaction->amount
        ]);

        // Reverse old transaction effect
        $this->reverseOldTransactionEffect($originalType, $originalAccountId, $originalCategoryId, $originalAmount);
        
        // Apply new transaction effect
        Log::info("🔄 COMPLEX UPDATE - Applying new effects", [
            'transaction_id' => $transaction->id
        ]);
        
        $this->updateAccountBalance($transaction, 'create');
        
        if ($transaction->type === 'expense') {
            $this->updateBudget($transaction, 'create');
        }
    }

    /**
     * Reverse effect of old transaction values
     */
    private function reverseOldTransactionEffect(string $originalType, int $originalAccountId, int $originalCategoryId, float $originalAmount): void
    {
        // Reverse account balance for old values
        if ($originalAccountId) {
            $oldAccount = Account::find($originalAccountId);
            
            if ($oldAccount) {
                $oldBalance = $oldAccount->current_balance;

                Log::info("↩️ REVERSING ACCOUNT EFFECT", [
                    'account_id' => $oldAccount->id,
                    'original_type' => $originalType,
                    'original_amount' => $originalAmount,
                    'old_balance' => $oldBalance
                ]);

                switch ($originalType) {
                    case 'income':
                    case 'savings':
                        $oldAccount->decrement('current_balance', $originalAmount);
                        break;
                    case 'expense':
                        $oldAccount->increment('current_balance', $originalAmount);
                        break;
                }

                $oldAccount->refresh();

                Log::info("↩️ ACCOUNT EFFECT REVERSED", [
                    'account_id' => $oldAccount->id,
                    'new_balance' => $oldAccount->current_balance,
                    'balance_change' => $oldAccount->current_balance - $oldBalance
                ]);
            }
        }

        // Reverse budget for old expense
        if ($originalType === 'expense' && $originalCategoryId) {
            $oldBudget = Budget::where('category_id', $originalCategoryId)
                ->active()
                ->first();

            if ($oldBudget) {
                $oldSpent = $oldBudget->spent_amount;
                
                Log::info("↩️ REVERSING BUDGET EFFECT", [
                    'budget_id' => $oldBudget->id,
                    'original_category_id' => $originalCategoryId,
                    'original_amount' => $originalAmount,
                    'old_spent' => $oldSpent
                ]);

                $oldBudget->decrement('spent_amount', $originalAmount);
                
                if ($oldBudget->spent_amount < 0) {
                    $oldBudget->update(['spent_amount' => 0]);
                }

                $oldBudget->refresh();

                Log::info("↩️ BUDGET EFFECT REVERSED", [
                    'budget_id' => $oldBudget->id,
                    'new_spent' => $oldBudget->spent_amount,
                    'spent_change' => $oldBudget->spent_amount - $oldSpent
                ]);
            }
        }
    }
}