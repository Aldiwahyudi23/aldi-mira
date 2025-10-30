<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            // Nama Keluarga atau Nama Hubungan (Cth: Keluarga Bima & Tiara)
            $table->string('name', 150); 
            
            // Kode Unik untuk Join/Invitasi Keluarga (Bisa di-encrypt)
            // Walaupun code ini di-encrypt, kolomnya tidak perlu BLOB/TEXT, cukup string
            $table->string('invitation_code')->unique(); 

            // Pengaturan atau Detail Tambahan Keluarga (Cth: Preferred Currency, Tema Aplikasi)
            $table->json('settings')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
