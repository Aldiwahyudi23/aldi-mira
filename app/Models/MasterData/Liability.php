<?php

namespace App\Models\MasterData;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name', // Cth: KPR Bank XYZ, Utang Pribadi
        'initial_amount',
        'remaining_balance', // Saldo yang berkurang saat pembayaran
        'due_date',
        'payment_frequency', // Cth: monthly, yearly
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'due_date' => 'date',
    ];

    // Relasi: Liabilitas dimiliki oleh satu User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
