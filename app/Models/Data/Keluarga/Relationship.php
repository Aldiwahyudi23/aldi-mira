<?php

namespace App\Models\Data\Keluarga;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'related_person_id',
        'type',
        'marriage_id',
    ];

    // 🔗 Relasi ke Person utama
    public function person()
    {
        return $this->belongsTo(Persons::class, 'person_id');
    }

    // 🔗 Relasi ke orang yang berhubungan
    public function relatedPerson()
    {
        return $this->belongsTo(Persons::class, 'related_person_id');
    }

    // 🔗 Relasi ke pernikahan
    public function marriage()
    {
        return $this->belongsTo(Marriage::class);
    }
}
