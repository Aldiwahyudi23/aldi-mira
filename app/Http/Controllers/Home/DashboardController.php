<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Account;
use App\Models\MasterData\Transaction;
use App\Models\Project\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use function Termwind\render;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. Data Keuangan Bersama - Total balance dari semua account joint
        $totalJointBalance = $this->getTotalJointBalance($user);
        
        // 2. Data Tabungan Impian - Progress project ongoing/planning
        $dreamSavingsData = $this->getDreamSavingsProgress($user);
        
        // 3. Data Statistik Keuangan Bulan Ini
        $monthlyStats = $this->getMonthlyFinancialStats($user);
        
        // 4. Data tambahan untuk dashboard
        $additionalData = $this->getAdditionalDashboardData($user);

        return Inertia::render('Home/Dashboard', [
            'dashboardData' => [
                'totalJointBalance' => $totalJointBalance,
                'dreamSavings' => $dreamSavingsData,
                'monthlyStats' => $monthlyStats,
                'additionalData' => $additionalData,
            ]
        ]);
    }

    private function getTotalJointBalance($user)
    {
        // Ambil semua account joint yang dimiliki user atau partner dalam family yang sama
        $familyAccounts = Account::where('type', 'joint')
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('user', function($q) use ($user) {
                          $q->where('family_id', $user->family_id)
                            ->where('id', '!=', $user->id);
                      });
            })
            ->with('user')
            ->get();

        $totalBalance = $familyAccounts->sum('current_balance');
        
        return [
            'amount' => $totalBalance,
            'formatted_amount' => 'Rp ' . number_format($totalBalance, 0, ',', '.'),
            'accounts_count' => $familyAccounts->count(),
            'accounts' => $familyAccounts->map(function($account) {
                return [
                    'name' => $account->name,
                    'balance' => $account->current_balance,
                    'formatted_balance' => 'Rp ' . number_format($account->current_balance, 0, ',', '.'),
                    'owner' => $account->user->name,
                ];
            })
        ];
    }

    private function getDreamSavingsProgress($user)
    {
        // Ambil project yang statusnya planning atau on_going melalui kategori yang dapat diakses user
        $projects = Project::whereIn('status', ['planning', 'on_going'])
            ->whereHas('category', function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    // Kategori milik user yang login
                    $q->where('user_id', $user->id)
                      // Atau kategori milik partner dalam family yang sama
                      ->orWhereHas('user', function($userQuery) use ($user) {
                          $userQuery->where('family_id', $user->family_id)
                                   ->where('id', '!=', $user->id);
                      });
                });
            })
            ->with(['category.user', 'items'])
            ->get();

        $totalPlanned = 0;
        $totalActual = 0;

        foreach ($projects as $project) {
            // Hitung total planned amount dari semua items
            $projectPlanned = $project->items->sum('planned_amount');
            $projectActual = $project->items->sum('actual_spent');
            
            $totalPlanned += $projectPlanned;
            $totalActual += $projectActual;
        }

        $progressPercentage = $totalPlanned > 0 ? ($totalActual / $totalPlanned) * 100 : 0;

        return [
            'progress_percentage' => round($progressPercentage, 1),
            'total_planned' => $totalPlanned,
            'total_actual' => $totalActual,
            'formatted_planned' => 'Rp ' . number_format($totalPlanned, 0, ',', '.'),
            'formatted_actual' => 'Rp ' . number_format($totalActual, 0, ',', '.'),
            'projects_count' => $projects->count(),
            'projects' => $projects->map(function($project) {
                $projectPlanned = $project->items->sum('planned_amount');
                $projectActual = $project->items->sum('actual_spent');
                $projectProgress = $projectPlanned > 0 ? ($projectActual / $projectPlanned) * 100 : 0;

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'target_amount' => $project->target_total_amount,
                    'formatted_target' => 'Rp ' . number_format($project->target_total_amount, 0, ',', '.'),
                    'planned_amount' => $projectPlanned,
                    'actual_amount' => $projectActual,
                    'progress_percentage' => round($projectProgress, 1),
                    'category_name' => $project->category->name ?? 'Uncategorized',
                    'owner' => $project->category->user->name ?? 'Unknown',
                    'is_owner' => $project->category->user_id === Auth::id(),
                ];
            })
        ];
    }

    private function getMonthlyFinancialStats($user)
    {
        $currentMonth = now()->format('Y-m');
        
         // Ambil transaksi berdasarkan category yang type-nya joint dan user yang login
        $transactions = Transaction::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                ->orWhereHas('user', function($userQuery) use ($user) {
                    $userQuery->where('family_id', $user->family_id)
                                ->where('id', '!=', $user->id);
                });
            })
            ->where(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m")'), $currentMonth)
            ->get();


            // DD($transactions);
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $totalSavings = $transactions->where('type', 'savings')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        // DD($transactions);
        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_savings' => $totalSavings,
            'net_balance' => $netBalance,
            'formatted_income' => 'Rp ' . number_format($totalIncome, 0, ',', '.'),
            'formatted_expense' => 'Rp ' . number_format($totalExpense, 0, ',', '.'),
            'formatted_savings' => 'Rp ' . number_format($totalSavings, 0, ',', '.'),
            'formatted_net_balance' => 'Rp ' . number_format($netBalance, 0, ',', '.'),
            'transaction_count' => $transactions->count(),
        ];
    }

    private function getAdditionalDashboardData($user)
    {
        // Data tambahan untuk insight
        
        // Transaksi terbaru
        $recentTransactions = Transaction::whereHas('account', function($query) use ($user) {
                $query->where('type', 'joint')
                      ->where(function($q) use ($user) {
                          $q->where('user_id', $user->id)
                            ->orWhereHas('user', function($userQuery) use ($user) {
                                $userQuery->where('family_id', $user->family_id);
                            });
                      });
            })
            ->with(['account', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'transaction_date' => $transaction->transaction_date->format('d M Y'),
                    'account_name' => $transaction->account->name ?? 'Unknown Account',
                    'category_name' => $transaction->category->name ?? 'Uncategorized',
                ];
            });

        // Project mendatang melalui kategori
        $upcomingProjects = Project::whereIn('status', ['planning', 'on_going'])
            ->whereHas('category', function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('user', function($userQuery) use ($user) {
                          $userQuery->where('family_id', $user->family_id)
                                   ->where('id', '!=', $user->id);
                      });
                });
            })
            ->where('target_completion_date', '>=', now())
            ->with(['category.user'])
            ->orderBy('target_completion_date', 'asc')
            ->limit(3)
            ->get()
            ->map(function($project) {
                $totalPlanned = $project->items->sum('planned_amount');
                $totalActual = $project->items->sum('actual_spent');
                $progress = $totalPlanned > 0 ? ($totalActual / $totalPlanned) * 100 : 0;

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'target_amount' => $project->target_total_amount,
                    'formatted_target' => 'Rp ' . number_format($project->target_total_amount, 0, ',', '.'),
                    'target_completion_date' => $project->target_completion_date?->format('d M Y'),
                    'progress_percentage' => round($progress, 1),
                    'category_name' => $project->category->name ?? 'Uncategorized',
                    'owner' => $project->category->user->name ?? 'Unknown',
                ];
            });

        return [
            'recent_transactions' => $recentTransactions,
            'upcoming_projects' => $upcomingProjects,
        ];
    }
}