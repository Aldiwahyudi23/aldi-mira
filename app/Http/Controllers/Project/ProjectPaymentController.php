<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Account;
use App\Models\MasterData\Transaction;
use App\Models\Project\ProjectPayment;
use App\Models\Project\ProjectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectPaymentController extends Controller
{
public function index(ProjectItem $projectItem)
{
    try {
        // Check access
        if (!$projectItem->project || !$projectItem->project->category) {
            return response()->json([
                'error' => 'Project atau kategori tidak ditemukan.'
            ], 404);
        }

        if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke item ini.'
            ], 403);
        }

        $payments = $projectItem->payments()
            ->with(['transaction.account', 'transaction.category'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSavings = $payments->where('amount', '>', 0)->sum('amount');
        
        // SESUAIKAN DENGAN DATABASE: 'complete' bukan 'completed'
        $isReadyForPurchase = $projectItem->status === 'ready' && $totalSavings >= $projectItem->planned_amount;

        return response()->json([
            'success' => true,
            'payments' => $payments,
            'total_savings' => $totalSavings,
            'project_item' => [
                'id' => $projectItem->id,
                'item_name' => $projectItem->item_name,
                'planned_amount' => $projectItem->planned_amount,
                'actual_spent' => $projectItem->actual_spent,
                'status' => $projectItem->status,
                'remaining_amount' => max(0, $projectItem->planned_amount - $projectItem->actual_spent),
                'savings_progress' => $projectItem->planned_amount > 0 ? 
                    min(100, ($projectItem->actual_spent / $projectItem->planned_amount) * 100) : 0,
                'is_ready_for_purchase' => $isReadyForPurchase
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error in payments index:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Terjadi kesalahan saat memuat data pembayaran: ' . $e->getMessage()
        ], 500);
    }
}


    public function store(Request $request, ProjectItem $projectItem)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:accounts,id',
            'payment_method' => 'required|in:transfer,cash',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            // Check access
            if (!$projectItem->project || !$projectItem->project->category) {
                return response()->json([
                    'error' => 'Project atau kategori tidak ditemukan.'
                ], 404);
            }

            if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
                return response()->json([
                    'error' => 'Anda tidak memiliki akses ke item ini.'
                ], 403);
            }

            // Check if already ready for purchase
            if ($projectItem->status === 'ready' || $projectItem->status === 'complete') {
                return response()->json([
                    'error' => 'Item sudah siap untuk pembelian atau sudah selesai. Tidak bisa menambah tabungan lagi.'
                ], 422);
            }

            // Check account ownership
            $account = Account::where('id', $request->account_id)
                ->where('user_id', Auth::id())
                ->orWhereHas('user', function($q) {
                    $q->where('family_id', Auth::user()->family_id)
                      ->where('type', 'joint');
                })
                ->first();

            if (!$account) {
                return response()->json([
                    'error' => 'Akun tidak valid atau tidak ditemukan.'
                ], 422);
            }

            DB::beginTransaction();
            
            try {
                // 1. Create Transaction as SAVINGS
                $transactionDescription = "Penyimpanan untuk {$projectItem->name} - {$projectItem->project->name}";
                
                $transaction = Transaction::create([
                    'user_id' => Auth::id(),
                    'account_id' => $request->account_id,
                    'category_id' => $projectItem->project->category_id,
                    'amount' => $request->amount,
                    'type' => 'savings',
                    'description' => $transactionDescription,
                    'transaction_date' => $request->transaction_date,
                ]);

                // 2. Create Project Payment
                $payment = ProjectPayment::create([
                    'project_item_id' => $projectItem->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method,
                    'note' => $request->note,
                ]);
                // 3. Update project item status dan actual_spent
                $this->updateProjectItemStatus($projectItem);

                DB::commit();

                // Reload payment dengan relationship
                $payment->load(['transaction.account', 'transaction.category']);
                $projectItem->refresh();

                return response()->json([
                    'success' => true,
                    'payment' => $payment,
                    'project_item' => [
                        'id' => $projectItem->id,
                        'item_name' => $projectItem->item_name,
                        'planned_amount' => $projectItem->planned_amount,
                        'actual_spent' => $projectItem->actual_spent,
                        'status' => $projectItem->status,
                        'remaining_amount' => max(0, $projectItem->planned_amount - $projectItem->actual_spent),
                        'savings_progress' => $projectItem->planned_amount > 0 ? 
                            min(100, ($projectItem->actual_spent / $projectItem->planned_amount) * 100) : 0,
                        'is_ready_for_purchase' => $projectItem->status === 'ready'
                    ],
                    'message' => 'Tabungan berhasil dicatat! 💫'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error in payment transaction:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error creating payment:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Gagal mencatat tabungan: ' . $e->getMessage()
            ], 500);
        }
    }

public function purchase(Request $request, ProjectItem $projectItem)
{
    $request->validate([
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required|exists:accounts,id',
        'payment_method' => 'required|in:transfer,cash',
        'transaction_date' => 'required|date',
        'note' => 'nullable|string|max:255',
    ]);

    try {
        if (!$projectItem->project || !$projectItem->project->category) {
            return response()->json(['error' => 'Project atau kategori tidak ditemukan.'], 404);
        }

        if (!$projectItem->project->category->isAccessibleBy(Auth::id())) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke item ini.'], 403);
        }

        if (!$projectItem->isReadyForPurchase()) {
            return response()->json(['error' => 'Item belum siap untuk pembelian. Tabungan belum mencukupi.'], 422);
        }

        $account = Account::where('id', $request->account_id)
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('family_id', Auth::user()->family_id)
                                ->where('type', 'joint');
                  });
            })
            ->first();

        if (!$account) {
            return response()->json(['error' => 'Akun tidak valid atau tidak ditemukan.'], 422);
        }

        DB::beginTransaction();

        // 1. Catat transaksi pengeluaran
        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'account_id' => $request->account_id,
            'category_id' => $projectItem->project->category_id,
            'amount' => $request->amount,
            'type' => 'expense',
            'description' => "Pembelian Item {$projectItem->name} dari Project {$projectItem->project->category->name} {$projectItem->project->name}",
            'transaction_date' => $request->transaction_date ?? now(),
        ]);

        // 2. Catat payment negatif
        $payment = $projectItem->payments()->create([
            'transaction_id' => $transaction->id,
            'amount' => -$request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        // 3. Update project item
        $newSpent = $projectItem->actual_spent - $request->amount;
        if ($newSpent < 0) $newSpent = 0;

        $projectItem->update([
            'status' => 'complete',
            'actual_spent' => $newSpent,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'payment' => $payment->load(['transaction.account', 'transaction.category']),
            'project_item' => $projectItem->fresh(),
            'message' => 'Pembelian berhasil dicatat! 🛍️'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating purchase:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'error' => 'Gagal mencatat pembelian: ' . $e->getMessage()
        ], 500);
    }
}


    public function destroy(ProjectItem $projectItem, ProjectPayment $payment)
    {
        if (!$projectItem->project->category->isAccessibleBy(Auth::id()) || 
            $payment->project_item_id !== $projectItem->id) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke pembayaran ini.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Hapus transaction jika ada
            if ($payment->transaction) {
                $payment->transaction->delete();
            }

            $payment->delete();

            // Update project item status
            $this->updateProjectItemStatus($projectItem);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Catatan pembayaran berhasil dihapus! 🗑️'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Gagal menghapus catatan pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update project item status berdasarkan total savings
     */
    private function updateProjectItemStatus(ProjectItem $projectItem)
    {
        $totalSavings = $projectItem->payments()
            ->where('amount', '>', 0) // Hanya yang positif (tabungan)
            ->sum('amount');

        $status = 'pending';
        if ($totalSavings > 0) {
            $status = 'in_progress';
        }
        if ($totalSavings >= $projectItem->planned_amount) {
            $status = 'ready';
        }

        $projectItem->update([
            'actual_spent' => $totalSavings,
            'status' => $status
        ]);
    }

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
}