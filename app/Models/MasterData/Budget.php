<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'period_start', 
        'period_end',
        'target_amount',
        'spent_amount', // 🎯 OBSERVER YANG AKAN UPDATE INI
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date', 
        'target_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
    ];

    // 🎯 HAPUS SEMUA METHOD YANG BISA UPDATE SPENT_AMOUNT
    // 🎯 OBSERVER YANG AKAN HANDLE SEMUA SPENT AMOUNT UPDATES

    // Relasi: Budget dimiliki oleh satu Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi: Budget memiliki banyak Transaksi melalui Kategori
    public function transactions()
    {
        return $this->hasManyThrough(
            Transaction::class,
            Category::class,
            'id', // Foreign key pada categories table
            'category_id', // Foreign key pada transactions table  
            'category_id', // Local key pada budgets table
            'id' // Local key pada categories table
        );
    }

    // Scope query untuk budget yang aktif (periode saat ini)
    public function scopeActive($query)
    {
        $now = now()->format('Y-m-d');
        return $query->where('period_start', '<=', $now)
                    ->where('period_end', '>=', $now);
    }

    // Scope query untuk budget berdasarkan kategori tertentu
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Scope query untuk budget dalam periode tertentu
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }

    // Scope query untuk budget yang bisa diakses user
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->whereHas('category', function($q) use ($userId) {
            $q->accessibleBy($userId);
        });
    }

    // 🎯 HANYA READ-ONLY METHODS & CALCULATED PROPERTIES

    // Helper method: hitung sisa anggaran (READ-ONLY)
    public function getRemainingAmountAttribute()
    {
        return $this->target_amount - $this->spent_amount;
    }

    // Helper method: hitung persentase penggunaan anggaran (READ-ONLY)
    public function getUsagePercentageAttribute()
    {
        if ($this->target_amount == 0) {
            return 0;
        }
        return ($this->spent_amount / $this->target_amount) * 100;
    }

    // Helper method: format persentase untuk display (READ-ONLY)
    public function getFormattedUsagePercentageAttribute()
    {
        return round($this->usage_percentage, 1) . '%';
    }

    // Helper method: cek apakah anggaran sudah melebihi target (READ-ONLY)
    public function getIsOverBudgetAttribute()
    {
        return $this->spent_amount > $this->target_amount;
    }

    // Helper method: cek status anggaran berdasarkan persentase (READ-ONLY)
    public function getBudgetStatusAttribute()
    {
        $percentage = $this->usage_percentage;

        if ($percentage >= 100) {
            return 'over_budget';
        } elseif ($percentage >= 80) {
            return 'warning'; 
        } elseif ($percentage >= 50) {
            return 'moderate';
        } else {
            return 'safe';
        }
    }

    // Helper method: format status anggaran untuk display (READ-ONLY)
    public function getBudgetStatusTextAttribute()
    {
        $statusMap = [
            'over_budget' => 'Melebihi Anggaran',
            'warning' => 'Perhatian',
            'moderate' => 'Moderat', 
            'safe' => 'Aman'
        ];

        return $statusMap[$this->budget_status] ?? 'Tidak Diketahui';
    }

    // Helper method: warna status anggaran (READ-ONLY)
    public function getBudgetStatusColorAttribute()
    {
        $colorMap = [
            'over_budget' => 'text-red-600 bg-red-100',
            'warning' => 'text-orange-600 bg-orange-100',
            'moderate' => 'text-yellow-600 bg-yellow-100',
            'safe' => 'text-green-600 bg-green-100'
        ];

        return $colorMap[$this->budget_status] ?? 'text-gray-600 bg-gray-100';
    }

    // Helper method: icon status anggaran (READ-ONLY)
    public function getBudgetStatusIconAttribute()
    {
        $iconMap = [
            'over_budget' => '⚠️',
            'warning' => '🔶', 
            'moderate' => '🟡',
            'safe' => '✅'
        ];

        return $iconMap[$this->budget_status] ?? '📊';
    }

    // Helper method: cek apakah budget dalam periode aktif (READ-ONLY)
    public function isActive()
    {
        $now = now()->format('Y-m-d');
        return $now >= $this->period_start && $now <= $this->period_end;
    }

    // Helper method: cek apakah budget sudah berakhir (READ-ONLY)
    public function isExpired()
    {
        $now = now()->format('Y-m-d');
        return $now > $this->period_end;
    }

    // Helper method: cek apakah budget belum dimulai (READ-ONLY)
    public function isUpcoming()
    {
        $now = now()->format('Y-m-d');
        return $now < $this->period_start;
    }

    // Helper method: format periode budget untuk display (READ-ONLY)
    public function getFormattedPeriodAttribute()
    {
        $start = $this->period_start->format('d M Y');
        $end = $this->period_end->format('d M Y');
        return "{$start} - {$end}";
    }

    // Helper method: sisa hari dalam periode budget (READ-ONLY)
    public function getDaysRemainingAttribute()
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        $end = $this->period_end;
        $now = now();
        
        return $now->diffInDays($end, false);
    }

    // Helper method: progress bar width untuk UI (READ-ONLY)
    public function getProgressBarWidthAttribute()
    {
        $percentage = $this->usage_percentage;
        return min($percentage, 100); // Maksimal 100% untuk progress bar
    }

    // 🎯 STATIC METHODS (READ-ONLY OPERATIONS)

    // Static method: buat budget untuk bulan berikutnya berdasarkan budget bulan ini
    public static function createNextMonthBudget(Budget $currentBudget)
    {
        $nextMonthStart = \Carbon\Carbon::parse($currentBudget->period_start)->addMonth();
        $nextMonthEnd = \Carbon\Carbon::parse($currentBudget->period_end)->addMonth();

        // Cek apakah budget untuk bulan berikutnya sudah ada
        $existingBudget = self::where('category_id', $currentBudget->category_id)
            ->where('period_start', $nextMonthStart->format('Y-m-d'))
            ->first();

        if ($existingBudget) {
            return $existingBudget;
        }

        // Buat budget baru untuk bulan berikutnya
        return self::create([
            'category_id' => $currentBudget->category_id,
            'period_start' => $nextMonthStart->format('Y-m-d'),
            'period_end' => $nextMonthEnd->format('Y-m-d'),
            'target_amount' => $currentBudget->target_amount,
            'spent_amount' => 0, // 🎯 OBSERVER YANG AKAN UPDATE NANTI
        ]);
    }

    // Static method: get total budget untuk user tertentu (READ-ONLY)
    public static function getTotalBudget($userId, $period = null)
    {
        $query = self::accessibleBy($userId);

        if ($period) {
            $query->where('period_start', '<=', $period)
                  ->where('period_end', '>=', $period);
        }

        return $query->sum('target_amount');
    }

    // Static method: get total spent untuk user tertentu (READ-ONLY)
    public static function getTotalSpent($userId, $period = null)
    {
        $query = self::accessibleBy($userId);

        if ($period) {
            $query->where('period_start', '<=', $period)
                  ->where('period_end', '>=', $period);
        }

        return $query->sum('spent_amount');
    }

    // Static method: get total remaining budget untuk user tertentu (READ-ONLY)
    public static function getTotalRemaining($userId, $period = null)
    {
        $totalBudget = self::getTotalBudget($userId, $period);
        $totalSpent = self::getTotalSpent($userId, $period);
        
        return $totalBudget - $totalSpent;
    }

    // Static method: get budget progress summary untuk dashboard (READ-ONLY)
    public static function getBudgetSummary($userId)
    {
        $activeBudgets = self::accessibleBy($userId)->active()->get();

        $totalTarget = $activeBudgets->sum('target_amount');
        $totalSpent = $activeBudgets->sum('spent_amount');
        $totalRemaining = $totalTarget - $totalSpent;
        $averageUsage = $totalTarget > 0 ? ($totalSpent / $totalTarget) * 100 : 0;

        return [
            'total_budgets' => $activeBudgets->count(),
            'total_target' => $totalTarget,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalRemaining,
            'average_usage_percentage' => $averageUsage,
            'over_budget_count' => $activeBudgets->where('spent_amount', '>', 'target_amount')->count(),
            'warning_count' => $activeBudgets->filter(function($budget) {
                return $budget->usage_percentage >= 80 && $budget->usage_percentage < 100;
            })->count(),
            'safe_count' => $activeBudgets->filter(function($budget) {
                return $budget->usage_percentage < 50;
            })->count(),
        ];
    }

    // Static method: get budgets yang perlu perhatian (READ-ONLY)
    public static function getBudgetsNeedingAttention($userId, $limit = 5)
    {
        return self::accessibleBy($userId)
            ->active()
            ->get()
            ->filter(function($budget) {
                return in_array($budget->budget_status, ['over_budget', 'warning']);
            })
            ->sortByDesc('usage_percentage')
            ->take($limit);
    }

    // 🎯 PROTECTION: Pastikan tidak ada yang bisa update spent_amount secara manual
    public function setSpentAmountAttribute($value)
    {
        // 🚫 JANGAN biarkan spent_amount di-set manual
        // Observer yang akan handle semua spent amount updates
        if ($this->exists) {
            // Biarkan hanya untuk creation dengan nilai default
            if (!$this->getOriginal('spent_amount') && $value == 0) {
                $this->attributes['spent_amount'] = 0;
            }
            // Untuk update, biarkan observer yang handle
        } else {
            // Untuk new record, set default 0
            $this->attributes['spent_amount'] = 0;
        }
    }
}