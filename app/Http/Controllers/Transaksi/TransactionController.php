<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Transaction;
use App\Models\MasterData\Account;
use App\Models\MasterData\Category;
use App\Models\MasterData\Budget;
use App\Models\Project\ProjectPayment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['user', 'account', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

            if (request()->wantsJson()) {
                return response()->json($transactions);
            }   
        
        return Inertia::render('Transaksi/Index', [
            'transactions' => $transactions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $validated = $request->validate([
                'type' => 'required|in:income,expense,savings',
                'account_id' => 'required|exists:accounts,id',
                'category_id' => 'required|exists:categories,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
                'transaction_date' => 'required|date',
            ]);

            $validated['user_id'] = Auth::id();
            
            // 🎯 VALIDASI SALDO SEBELUM CREATE
            if ($validated['type'] === 'expense') {
                $this->validateSufficientBalance($validated['account_id'], $validated['amount']);
            }
            
            // Create transaction - Observer akan handle sisanya
            Transaction::create($validated);

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Transaksi berhasil dicatat!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        DB::beginTransaction();
        
        try {
            // Cek authorization
            if ($transaction->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk mengubah transaksi ini.',
                    'type' => 'error'
                ]);
            }
                        // 🛡️ Validasi penguncian ProjectPayment
            $this->validateProjectPaymentLock($transaction, 'ubah');


            $validated = $request->validate([
                'type' => 'required|in:income,expense,savings',
                'account_id' => 'required|exists:accounts,id',
                'category_id' => 'required|exists:categories,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
                'transaction_date' => 'required|date',
            ]);

            // 🎯 VALIDASI SALDO SEBELUM UPDATE (jika berubah menjadi expense atau amount berubah)
            if ($this->needsBalanceValidation($transaction, $validated)) {
                $this->validateSufficientBalance($validated['account_id'], $validated['amount']);
            }

            // Update transaction - Observer akan handle sisanya
            $transaction->update($validated);

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Transaksi berhasil diperbarui!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        try {
            // Cek authorization
            if ($transaction->user_id !== Auth::id()) {
                return redirect()->back()->with([
                    'error' => 'Anda tidak memiliki akses untuk menghapus transaksi ini.',
                    'type' => 'error'
                ]);
            }

              // 🛡️ Validasi penguncian ProjectPayment
            $this->validateProjectPaymentLock($transaction, 'hapus');

            // Delete transaction - Observer akan handle sisanya
            $transaction->delete();

            return redirect()->back()->with([
                'success' => 'Transaksi berhasil dihapus!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * 🎯 VALIDATE SUFFICIENT BALANCE FOR EXPENSE
     */
    private function validateSufficientBalance($accountId, $amount): void
    {
        $account = Account::find($accountId);
        
        if (!$account) {
            throw new \Exception("Akun tidak ditemukan.");
        }

        if ($account->current_balance < $amount) {
            $formattedBalance = 'Rp ' . number_format($account->current_balance, 0, ',', '.');
            $formattedAmount = 'Rp ' . number_format($amount, 0, ',', '.');
            $shortage = 'Rp ' . number_format($amount - $account->current_balance, 0, ',', '.');
            
            throw new \Exception(
                "Saldo akun {$account->name} tidak mencukupi! " .
                "Saldo tersedia: {$formattedBalance}, " .
                "Dibutuhkan: {$formattedAmount}, " .
                "Kekurangan: {$shortage}"
            );
        }
    }

    /**
     * 🎯 CHECK IF BALANCE VALIDATION IS NEEDED FOR UPDATE
     */
    private function needsBalanceValidation(Transaction $transaction, array $validated): bool
    {
        // Validasi diperlukan jika:
        // 1. Type berubah menjadi expense, ATAU
        // 2. Tetap expense tapi amount berubah menjadi lebih besar, ATAU  
        // 3. Account berubah dan type adalah expense
        
        $isBecomingExpense = $transaction->type !== 'expense' && $validated['type'] === 'expense';
        $isExpenseWithLargerAmount = $transaction->type === 'expense' && 
                                   $validated['type'] === 'expense' && 
                                   $validated['amount'] > $transaction->amount;
        $isChangingAccountWithExpense = $validated['type'] === 'expense' && 
                                      $validated['account_id'] !== $transaction->account_id;

        return $isBecomingExpense || $isExpenseWithLargerAmount || $isChangingAccountWithExpense;
    }

    /**
     * 🎯 GET ACCOUNT BALANCE FOR FRONTEND VALIDATION
     */
    public function getAccountBalance($accountId)
    {
        try {
            $account = Account::find($accountId);
            
            if (!$account) {
                return response()->json([
                    'error' => 'Account not found'
                ], 404);
            }

            return response()->json([
                'account_id' => $account->id,
                'account_name' => $account->name,
                'current_balance' => $account->current_balance,
                'formatted_balance' => 'Rp ' . number_format($account->current_balance, 0, ',', '.'),
                'is_sufficient' => true // Default, frontend akan handle logic
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get account balance',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🎯 CHECK TRANSACTION FEASIBILITY (for frontend pre-validation)
     */
    public function checkTransactionFeasibility(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:income,expense,savings',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0',
            ]);

            $account = Account::find($request->account_id);
            $amount = $request->amount;
            $type = $request->type;

            $isFeasible = true;
            $message = '';
            $shortage = 0;

            if ($type === 'expense') {
                if ($account->current_balance < $amount) {
                    $isFeasible = false;
                    $shortage = $amount - $account->current_balance;
                    $message = "Saldo tidak mencukupi. Kekurangan: Rp " . number_format($shortage, 0, ',', '.');
                } else {
                    $message = "Saldo mencukupi untuk transaksi ini.";
                }
            } else {
                $message = "Transaksi {$type} dapat dilakukan.";
            }

            return response()->json([
                'is_feasible' => $isFeasible,
                'message' => $message,
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'current_balance' => $account->current_balance,
                    'formatted_balance' => 'Rp ' . number_format($account->current_balance, 0, ',', '.')
                ],
                'transaction' => [
                    'type' => $type,
                    'amount' => $amount,
                    'formatted_amount' => 'Rp ' . number_format($amount, 0, ',', '.')
                ],
                'shortage' => $shortage,
                'formatted_shortage' => $shortage > 0 ? 'Rp ' . number_format($shortage, 0, ',', '.') : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all accessible accounts
     */
    public function getAccounts()
    {
        try {
            $accounts = Account::where('user_id', Auth::id())
                ->orWhereHas('user', function($q) {
                    $q->where('family_id', Auth::user()->family_id)
                      ->where('type', 'joint');
                })
                ->orderBy('name')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'type' => $account->type,
                        'current_balance' => $account->current_balance,
                        'formatted_balance' => 'Rp ' . number_format($account->current_balance, 0, ',', '.'),
                        'balance_status' => $account->balance_status,
                        'balance_status_color' => $account->balance_status_color,
                        'balance_status_text' => $account->balance_status_text
                    ];
                });

            return response()->json($accounts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load accounts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all accessible categories
     */
    public function getCategories()
    {
        try {
            $categories = Category::where('user_id', Auth::id())
                ->orWhereHas('user', function($q) {
                    $q->where('family_id', Auth::user()->family_id)
                      ->where('type', 'joint');
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories by budget type
     */
    public function getCategoriesByBudgetType($budgetType)
    {
        try {
            $categories = Category::where('user_id', Auth::id())
                ->orWhereHas('user', function($q) {
                    $q->where('family_id', Auth::user()->family_id)
                      ->where('type', 'joint');
                })
                ->where('budget_type', $budgetType)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset account balances (for debugging)
     */
    public function resetAccountBalances()
    {
        try {
            $accounts = Account::all();
            
            foreach ($accounts as $account) {
                $account->current_balance = 0;
                $account->save();
            }
            
            // Reset semua spent_amount di budget
            Budget::query()->update(['spent_amount' => 0]);
            
            return response()->json(['message' => 'All balances reset to zero']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
     /**
     * 🔒 Validasi jika transaksi terhubung dengan ProjectPayment
     */
    private function validateProjectPaymentLock(Transaction $transaction, string $action): void
    {
        $linkedPayment = ProjectPayment::where('transaction_id', $transaction->id)->first();

        if ($linkedPayment) {
            $categoryName = $transaction->category->name ?? 'Tidak diketahui';

            throw new Exception(
                "Transaksi ini merupakan bagian dari Project dan tidak dapat di{$action}. " .
                "Kategori: {$categoryName}. Silakan ubah atau hapus melalui halaman Project."
            );
        }
    }
}