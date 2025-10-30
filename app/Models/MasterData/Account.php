<?php

namespace App\Models\MasterData;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name', 
        'type', // personal, joint
        'current_balance',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
    ];

    // 🎯 HAPUS SEMUA METHOD YANG BISA UPDATE BALANCE
    // 🎯 OBSERVER YANG AKAN HANDLE SEMUA BALANCE UPDATES

    // Relasi: Akun dimiliki oleh satu User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: User melalui Family (untuk akses joint accounts)
    public function familyUsers()
    {
        return $this->user->family->users() ?? collect();
    }

    // Relasi: Akun memiliki banyak Transaksi
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope query untuk akun yang bisa diakses user
    public function scopeAccessibleBy($query, $userId)
    {
        $user = User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect([$userId]);

        return $query->where(function($q) use ($userId, $familyUserIds) {
            $q->where('user_id', $userId) // Akun personal user
              ->orWhere(function($q2) use ($familyUserIds) {
                  $q2->whereIn('user_id', $familyUserIds) // Akun dari user dalam family yang sama
                     ->where('type', 'joint'); // Hanya yang type joint
              });
        });
    }

    // Scope query untuk akun personal user tertentu
    public function scopePersonal($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->where('type', 'personal');
    }

    // Scope query untuk akun joint dalam family
    public function scopeJoint($query, $userId)
    {
        $user = User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect();

        return $query->whereIn('user_id', $familyUserIds)
                    ->where('type', 'joint');
    }

    // Scope query hanya akun aktif
    public function scopeActive($query)
    {
        return $query;
    }

    // Scope query berdasarkan tipe akun
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper method: cek apakah akun adalah joint
    public function isJoint()
    {
        return $this->type === 'joint';
    }

    // Helper method: cek apakah akun adalah personal
    public function isPersonal()
    {
        return $this->type === 'personal';
    }

    // Helper method: cek apakah user bisa mengakses akun ini
    public function isAccessibleBy($userId)
    {
        if ($this->isPersonal()) {
            return $this->user_id === $userId;
        }

        if ($this->isJoint()) {
            $user = User::find($userId);
            $familyUserIds = $user->family->users()->pluck('id') ?? collect();
            return $familyUserIds->contains($this->user_id);
        }

        return false;
    }

    // 🎯 HANYA READ-ONLY METHODS & CALCULATED PROPERTIES

    // Helper method: format saldo untuk display (READ-ONLY)
    public function getFormattedBalanceAttribute()
    {
        return 'Rp ' . number_format($this->current_balance, 0, ',', '.');
    }

    // Helper method: cek apakah saldo mencukupi untuk transaksi (READ-ONLY)
    public function hasSufficientBalance($amount)
    {
        return $this->current_balance >= $amount;
    }

    // Helper method: dapatkan persentase penggunaan (READ-ONLY)
    public function getBalanceUsagePercentage($plannedAmount = 0)
    {
        if ($plannedAmount <= 0) {
            return 0;
        }
        
        return ($this->current_balance / $plannedAmount) * 100;
    }

    // Helper method: status saldo berdasarkan threshold (READ-ONLY)
    public function getBalanceStatusAttribute()
    {
        if ($this->current_balance <= 0) {
            return 'empty';
        } elseif ($this->current_balance < 100000) { // Contoh threshold
            return 'low';
        } elseif ($this->current_balance < 1000000) {
            return 'medium';
        } else {
            return 'healthy';
        }
    }

    // Helper method: warna status saldo (READ-ONLY)
    public function getBalanceStatusColorAttribute()
    {
        $colors = [
            'empty' => 'text-red-600 bg-red-100',
            'low' => 'text-orange-600 bg-orange-100', 
            'medium' => 'text-yellow-600 bg-yellow-100',
            'healthy' => 'text-green-600 bg-green-100'
        ];

        return $colors[$this->balance_status] ?? 'text-gray-600 bg-gray-100';
    }

    // Helper method: teks status saldo (READ-ONLY)
    public function getBalanceStatusTextAttribute()
    {
        $texts = [
            'empty' => 'Saldo Kosong',
            'low' => 'Saldo Rendah',
            'medium' => 'Saldo Menengah', 
            'healthy' => 'Saldo Sehat'
        ];

        return $texts[$this->balance_status] ?? 'Tidak Diketahui';
    }

    // 🎯 STATIC METHODS (READ-ONLY OPERATIONS)

    // Static method: get total saldo semua akun yang bisa diakses user
    public static function getTotalBalance($userId)
    {
        return self::accessibleBy($userId)->sum('current_balance');
    }

    // Static method: get total saldo akun personal user
    public static function getPersonalBalance($userId)
    {
        return self::personal($userId)->sum('current_balance');
    }

    // Static method: get total saldo akun joint dalam family
    public static function getJointBalance($userId)
    {
        return self::joint($userId)->sum('current_balance');
    }

    // Static method: get ringkasan semua akun user
    public static function getAccountSummary($userId)
    {
        $personalBalance = self::getPersonalBalance($userId);
        $jointBalance = self::getJointBalance($userId);
        $totalBalance = $personalBalance + $jointBalance;

        return [
            'personal_balance' => $personalBalance,
            'joint_balance' => $jointBalance,
            'total_balance' => $totalBalance,
            'personal_accounts_count' => self::personal($userId)->count(),
            'joint_accounts_count' => self::joint($userId)->count(),
            'total_accounts_count' => self::accessibleBy($userId)->count(),
        ];
    }

    // 🎯 PROTECTION: Pastikan tidak ada yang bisa update balance secara manual
    // Dengan cara tidak provide method untuk update balance

    // 🎯 VALIDATION: Pastikan current_balance tidak bisa di-set manual
    public function setCurrentBalanceAttribute($value)
    {
        // 🚫 JANGAN biarkan current_balance di-set manual
        // Observer yang akan handle semua balance updates
        if ($this->exists) {
            // Biarkan hanya untuk creation dengan nilai default
            if (!$this->getOriginal('current_balance') && $value == 0) {
                $this->attributes['current_balance'] = 0;
            }
            // Untuk update, biarkan observer yang handle
        } else {
            // Untuk new record, set default 0
            $this->attributes['current_balance'] = 0;
        }
    }
}