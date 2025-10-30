<?php

namespace App\Models\Data\Keluarga;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marriage extends Model
{
    use HasFactory;

    protected $fillable = [
        'marriage_date',
        'divorce_date',
        'notes',
    ];

    // 🔗 Relasi ke relationship (spouse)
    public function relationships()
    {
        return $this->hasMany(Relationship::class);
    }

    // 🔗 Ambil pasangan yang menikah
    public function spouses()
    {
        return $this->belongsToMany(Persons::class, 'relationships')
            ->wherePivot('type', 'spouse');
    }
}
