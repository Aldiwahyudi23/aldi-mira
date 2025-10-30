<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'invitation_code',
        'settings',
        'is_active',
    ];

    // 🚫 Jangan tampilkan invitation_code saat toArray() / toJson()
    protected $hidden = [
        'invitation_code',
    ];

    // 🔒 Hash otomatis setiap kali di-set
    public function setInvitationCodeAttribute($value)
    {
        $this->attributes['invitation_code'] = Hash::make($value);
    }

    // ✅ Fungsi bantu untuk verifikasi kode undangan
    public function verifyInvitationCode(string $plainCode): bool
    {
        return Hash::check($plainCode, $this->invitation_code);
    }

    // Relasi ke users
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
