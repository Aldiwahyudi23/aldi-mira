<?php

namespace App\Models\Project;

use App\Models\MasterData\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'target_total_amount',
        'target_completion_date',
        'status',
    ];

    protected $casts = [
        'target_total_amount' => 'decimal:2',
        'target_completion_date' => 'date',
    ];

    // Relasi: Proyek terikat pada satu Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    // Relasi: Proyek memiliki banyak Item
    public function items()
    {
        return $this->hasMany(ProjectItem::class);
    }

    // Scope: Proyek yang bisa diakses oleh user
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->orWhereHas('category', function ($q) use ($userId) {
                $q->where('type', 'joint');
            });
    }

    // Scope: Proyek berdasarkan status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope: Proyek yang aktif (belum completed)
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    // Accessor untuk formatted amount
    public function getFormattedTargetAmountAttribute()
    {
        return number_format($this->target_total_amount, 0, ',', '.');
    }

    // Accessor untuk progress percentage
    public function getProgressPercentageAttribute()
    {
        $totalItems = $this->items()->count();
        if ($totalItems === 0) return 0;

        $completedItems = $this->items()->where('status', 'completed')->count();
        return ($completedItems / $totalItems) * 100;
    }
}