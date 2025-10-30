<?php

namespace App\Models\Data\Keluarga;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persons extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'gender',
        'birth_date',
        'death_date',
        'photo_path',
        'bio',
    ];

    // 🔗 Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Relasi ke relasi keluarga (relationships)
    public function relationships()
    {
        return $this->hasMany(Relationship::class);
    }

    // 🔗 Orang yang berelasi dengan dia
    public function relatedPersons()
    {
        return $this->belongsToMany(
            Persons::class,
            'relationships',
            'person_id',
            'related_person_id'
        )->withPivot('type', 'marriage_id')
         ->withTimestamps();
    }

    // 🔗 Relasi untuk pasangan (spouse)
    public function spouses()
    {
        return $this->relatedPersons()->wherePivot('type', 'spouse');
    }

    // 🔗 Relasi untuk anak-anak
    public function children()
    {
        return $this->relatedPersons()->wherePivot('type', 'child');
    }

    // 🔗 Relasi untuk orang tua
    public function parents()
    {
        return $this->relatedPersons()->wherePivot('type', 'parent');
    }
}
