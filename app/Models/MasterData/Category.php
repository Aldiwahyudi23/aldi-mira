<?php

namespace App\Models\MasterData;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // personal, joint
        'budget_type', // income, expense, savings
        'description',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi: Kategori dimiliki oleh User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: User melalui Family (untuk akses joint categories)
    public function familyUsers()
    {
        return $this->user->family->users() ?? collect();
    }

    // Relasi: Satu Kategori bisa memiliki banyak Anggaran
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    // Relasi: Satu Kategori bisa memiliki banyak Transaksi
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope query untuk kategori yang bisa diakses user
    public function scopeAccessibleBy($query, $userId)
    {
        $user = \App\Models\User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect([$userId]);

        return $query->where(function($q) use ($userId, $familyUserIds) {
            $q->where('user_id', $userId) // Kategori personal user
              ->orWhere(function($q2) use ($familyUserIds) {
                  $q2->whereIn('user_id', $familyUserIds) // Kategori dari user dalam family yang sama
                     ->where('type', 'joint'); // Hanya yang type joint
              });
        });
    }

    // Scope query untuk kategori personal user tertentu
    public function scopePersonal($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->where('type', 'personal');
    }

    // Scope query untuk kategori joint dalam family
    public function scopeJoint($query, $userId)
    {
        $user = \App\Models\User::find($userId);
        $familyUserIds = $user->family->users()->pluck('id') ?? collect();

        return $query->whereIn('user_id', $familyUserIds)
                    ->where('type', 'joint');
    }

    // Scope query berdasarkan budget type
    public function scopeByBudgetType($query, $type)
    {
        return $query->where('budget_type', $type);
    }

    // Scope query hanya yang aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper method: cek apakah kategori adalah joint
    public function isJoint()
    {
        return $this->type === 'joint';
    }

    // Helper method: cek apakah kategori adalah personal
    public function isPersonal()
    {
        return $this->type === 'personal';
    }

    // Helper method: cek apakah user bisa mengakses kategori ini
    public function isAccessibleBy($userId)
    {
        if ($this->isPersonal()) {
            return $this->user_id === $userId;
        }

        if ($this->isJoint()) {
            $user = \App\Models\User::find($userId);
            $familyUserIds = $user->family->users()->pluck('id') ?? collect();
            return $familyUserIds->contains($this->user_id);
        }

        return false;
    }
}