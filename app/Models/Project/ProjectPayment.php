<?php

namespace App\Models\Project;

use App\Models\MasterData\Transaction;
use Illuminate\Database\Eloquent\Model;

class ProjectPayment extends Model
{
    protected $fillable = [
        'project_item_id',
        'transaction_id',
        'amount',
        'payment_method',
        'note'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationship
    public function projectItem()
    {
        return $this->belongsTo(ProjectItem::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Scope untuk tabungan (amount positif)
    public function scopeSavings($query)
    {
        return $query->where('amount', '>', 0);
    }

    // Scope untuk pengeluaran (amount negatif)
    public function scopeExpenses($query)
    {
        return $query->where('amount', '<', 0);
    }
}