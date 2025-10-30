<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'item_type',
        'name',
        'description',
        'planned_amount',
        'actual_spent',
        'status',
        'details',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_spent' => 'decimal:2',
        'details' => 'array',
    ];

    // Relasi ke Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

        public function checklists()
    {
        return $this->hasMany(ItemChecklist::class);
    }

    // Relasi ke Payments (jika ada)
    public function payments()
    {
        return $this->hasMany(ProjectPayment::class);
    }

    public function totalPayments()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function isReadyForPurchase()
    {
        return $this->status === 'ready' && $this->actual_spent >= $this->planned_amount;
    }


    // Accessor untuk formatted amounts
        public function getFormattedPlannedAmountAttribute()
    {
        return number_format($this->planned_amount, 0, ',', '.');
    }

    public function getFormattedActualSpentAttribute()
    {
        return number_format($this->actual_spent, 0, ',', '.');
    }

    // Accessor untuk progress percentage
    public function getProgressPercentageAttribute()
    {
        if (!$this->planned_amount || $this->planned_amount == 0) {
            return 0;
        }
        return ($this->actual_spent / $this->planned_amount) * 100;
    }

    // Accessor untuk remaining amount
    public function getRemainingAmountAttribute()
    {
        return $this->planned_amount - $this->actual_spent;
    }

    // Scope untuk item type
    public function scopeByType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    // Scope untuk status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk active items (bukan cancelled)
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    // Method untuk update actual spent dari payments
    public function updateActualSpent()
    {
        $totalSpent = $this->payments()->sum('amount');
        $this->update(['actual_spent' => $totalSpent]);
    }

    // Method untuk mendapatkan detail berdasarkan type
    public function getDetail($key, $default = null)
    {
        return data_get($this->details, $key, $default);
    }

    // Method untuk set detail
    public function setDetail($key, $value)
    {
        $details = $this->details ?? [];
        data_set($details, $key, $value);
        $this->details = $details;
    }
}