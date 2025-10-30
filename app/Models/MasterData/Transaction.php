<?php

namespace App\Models\MasterData;

use App\Models\Project\ProjectPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id', 
        'category_id',
        'amount',
        'type', // income, expense, savings
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    // 🎯 HAPUS SEMUA METHOD YANG BISA UPDATE BALANCE/BUDGET
    // 🎯 OBSERVER YANG AKAN HANDLE SEMUA BUSINESS LOGIC

    // Relasi: Transaksi dibuat oleh User
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // Relasi: Transaksi berasal dari satu Akun
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // Relasi: Transaksi terikat pada satu Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function payment()
    {
        return $this->hasOne(ProjectPayment::class, 'transaction_id');
    }


    // Relasi: Transaksi bisa mempengaruhi banyak Budget (melalui kategori)
    public function budgets()
    {
        return $this->hasManyThrough(
            Budget::class,
            Category::class,
            'id', // Foreign key pada categories table
            'category_id', // Foreign key pada budgets table
            'category_id', // Local key pada transactions table
            'id' // Local key pada categories table
        )->where(function($query) {
            $query->where('period_start', '<=', $this->transaction_date)
                  ->where('period_end', '>=', $this->transaction_date);
        });
    }

    // Scope query untuk transaksi yang bisa diakses user
    public function scopeAccessibleBy($query, $userId)
    {
        $user = \App\Models\User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect([$userId]);

        return $query->where(function($q) use ($userId, $familyUserIds) {
            // Transaksi personal user
            $q->where('user_id', $userId)
              // Atau transaksi dari akun joint yang bisa diakses
              ->orWhereHas('account', function($q2) use ($familyUserIds) {
                  $q2->whereIn('user_id', $familyUserIds)
                     ->where('type', 'joint');
              })
              // Atau transaksi dengan kategori joint yang bisa diakses
              ->orWhereHas('category', function($q3) use ($familyUserIds) {
                  $q3->whereIn('user_id', $familyUserIds)
                     ->where('type', 'joint');
              });
        });
    }

    // Scope query untuk transaksi personal user
    public function scopePersonal($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->whereHas('account', function($q) use ($userId) {
                        $q->where('user_id', $userId)
                          ->where('type', 'personal');
                    })
                    ->whereHas('category', function($q) use ($userId) {
                        $q->where('user_id', $userId)
                          ->where('type', 'personal');
                    });
    }

    // Scope query untuk transaksi joint dalam family
    public function scopeJoint($query, $userId)
    {
        $user = \App\Models\User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect();

        return $query->where(function($q) use ($familyUserIds) {
            $q->whereHas('account', function($q2) use ($familyUserIds) {
                  $q2->whereIn('user_id', $familyUserIds)
                     ->where('type', 'joint');
              })
              ->orWhereHas('category', function($q3) use ($familyUserIds) {
                  $q3->whereIn('user_id', $familyUserIds)
                     ->where('type', 'joint');
              });
        });
    }

    // Scope query berdasarkan tipe transaksi
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope query berdasarkan kategori
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Scope query berdasarkan akun
    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // Scope query berdasarkan periode tanggal
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Scope query untuk transaksi bulan ini
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    // Scope query untuk transaksi minggu ini
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    // Scope query untuk transaksi hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }

    // Scope query hanya transaksi aktif
    public function scopeActive($query)
    {
        return $query;
    }

    // 🎯 HANYA READ-ONLY METHODS & CALCULATED PROPERTIES

    // Helper method: format amount untuk display dengan simbol (READ-ONLY)
    public function getFormattedAmountAttribute()
    {
        $formatted = 'Rp ' . number_format($this->amount, 0, ',', '.');
        
        if ($this->type === 'income') {
            return '+ ' . $formatted;
        } elseif ($this->type === 'expense') {
            return '- ' . $formatted;
        } else {
            return $formatted; // untuk savings tanpa simbol
        }
    }

    // Helper method: warna amount berdasarkan tipe (READ-ONLY)
    public function getAmountColorAttribute()
    {
        return match($this->type) {
            'income' => 'text-green-600',
            'expense' => 'text-red-600', 
            'savings' => 'text-blue-600',
            default => 'text-gray-600'
        };
    }

    // Helper method: background color berdasarkan tipe (READ-ONLY)
    public function getAmountBgColorAttribute()
    {
        return match($this->type) {
            'income' => 'bg-green-50',
            'expense' => 'bg-red-50',
            'savings' => 'bg-blue-50', 
            default => 'bg-gray-50'
        };
    }

    // Helper method: icon berdasarkan tipe transaksi (READ-ONLY)
    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'income' => '💰',
            'expense' => '💸',
            'savings' => '🏦',
            default => '📊'
        };
    }

    // Helper method: label tipe transaksi (READ-ONLY)
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran', 
            'savings' => 'Tabungan',
            default => 'Transaksi'
        };
    }

    // Helper method: badge variant untuk UI (READ-ONLY)
    public function getTypeBadgeVariantAttribute()
    {
        return match($this->type) {
            'income' => 'success',
            'expense' => 'danger',
            'savings' => 'primary',
            default => 'secondary'
        };
    }

    // Helper method: format tanggal transaksi (READ-ONLY)
    public function getFormattedDateAttribute()
    {
        return $this->transaction_date->format('d M Y');
    }

    // Helper method: format tanggal dengan hari (READ-ONLY)
    public function getFormattedDateWithDayAttribute()
    {
        return $this->transaction_date->format('l, d M Y');
    }

    // Helper method: cek apakah transaksi adalah pemasukan (READ-ONLY)
    public function isIncome()
    {
        return $this->type === 'income';
    }

    // Helper method: cek apakah transaksi adalah pengeluaran (READ-ONLY)
    public function isExpense()
    {
        return $this->type === 'expense';
    }

    // Helper method: cek apakah transaksi adalah tabungan (READ-ONLY)
    public function isSavings()
    {
        return $this->type === 'savings';
    }

    // Helper method: cek apakah transaksi baru dibuat (READ-ONLY)
    public function getIsRecentAttribute()
    {
        return $this->created_at->gt(now()->subHours(24));
    }

    // Helper method: cek apakah user bisa mengakses transaksi ini (READ-ONLY)
    public function isAccessibleBy($userId)
    {
        // User bisa mengakses transaksi yang mereka buat
        if ($this->user_id === $userId) {
            return true;
        }

        $user = \App\Models\User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect();

        // User bisa mengakses transaksi yang menggunakan akun joint dalam family
        if ($this->account->isJoint() && $familyUserIds->contains($this->account->user_id)) {
            return true;
        }

        // User bisa mengakses transaksi yang menggunakan kategori joint dalam family
        if ($this->category->isJoint() && $familyUserIds->contains($this->category->user_id)) {
            return true;
        }

        return false;
    }

    // Helper method: cek apakah transaksi mempengaruhi budget aktif (READ-ONLY)
    public function affectsActiveBudget()
    {
        if (!$this->isExpense()) {
            return false;
        }

        return Budget::where('category_id', $this->category_id)
            ->active()
            ->exists();
    }

    // Helper method: dapatkan budget aktif yang terpengaruh (READ-ONLY)
    public function getAffectedBudgetsAttribute()
    {
        if (!$this->isExpense()) {
            return collect();
        }

        return Budget::where('category_id', $this->category_id)
            ->where('period_start', '<=', $this->transaction_date)
            ->where('period_end', '>=', $this->transaction_date)
            ->get();
    }

    // 🎯 STATIC METHODS (READ-ONLY OPERATIONS)

    // Static method: get total transaksi berdasarkan tipe (READ-ONLY)
    public static function getTotalByType($userId, $type, $period = null)
    {
        $query = self::accessibleBy($userId)->byType($type);

        if ($period === 'month') {
            $query->thisMonth();
        } elseif ($period === 'week') {
            $query->thisWeek();
        } elseif ($period === 'today') {
            $query->today();
        }

        return $query->sum('amount');
    }

    // Static method: get summary transaksi untuk dashboard (READ-ONLY)
    public static function getTransactionSummary($userId, $period = 'month')
    {
        $query = self::accessibleBy($userId);

        if ($period === 'month') {
            $query->thisMonth();
        } elseif ($period === 'week') {
            $query->thisWeek();
        } elseif ($period === 'today') {
            $query->today();
        }

        $totalIncome = (clone $query)->byType('income')->sum('amount');
        $totalExpense = (clone $query)->byType('expense')->sum('amount');
        $totalSavings = (clone $query)->byType('savings')->sum('amount');
        $netAmount = $totalIncome - $totalExpense;

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_savings' => $totalSavings,
            'net_amount' => $netAmount,
            'transaction_count' => $query->count(),
            'income_count' => (clone $query)->byType('income')->count(),
            'expense_count' => (clone $query)->byType('expense')->count(),
            'savings_count' => (clone $query)->byType('savings')->count(),
        ];
    }

    // Static method: get recent transactions (READ-ONLY)
    public static function getRecentTransactions($userId, $limit = 10)
    {
        return self::accessibleBy($userId)
            ->with(['account', 'category', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // Static method: get top categories untuk expense (READ-ONLY)
    public static function getTopExpenseCategories($userId, $period = 'month', $limit = 5)
    {
        $query = self::accessibleBy($userId)
            ->byType('expense')
            ->with('category');

        if ($period === 'month') {
            $query->thisMonth();
        } elseif ($period === 'week') {
            $query->thisWeek();
        }

        return $query->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                return [
                    'category' => $item->category,
                    'total_amount' => $item->total_amount,
                    'formatted_amount' => 'Rp ' . number_format($item->total_amount, 0, ',', '.')
                ];
            });
    }

    // Static method: get monthly trend (READ-ONLY)
    public static function getMonthlyTrend($userId, $months = 6)
    {
        $results = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            
            $income = self::accessibleBy($userId)
                ->byType('income')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount');
                
            $expense = self::accessibleBy($userId)
                ->byType('expense') 
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount');
                
            $results[] = [
                'month' => $month->format('M Y'),
                'income' => $income,
                'expense' => $expense,
                'savings' => $income - $expense
            ];
        }
        
        return $results;
    }

    // 🎯 HAPUS BOOT METHOD & SEMUA BUSINESS LOGIC METHODS
    // 🎯 OBSERVER YANG AKAN HANDLE SEMUA UPDATES

    // 🗑️ HAPUS METHODS INI:
    // - updateAccountBalance()
    // - updateRelatedBudgets() 
    // - boot() method dengan event listeners
}