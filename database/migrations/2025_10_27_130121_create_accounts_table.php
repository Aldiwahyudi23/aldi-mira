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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            // Siapa pemilik akun (Pasangan 1 atau Pasangan 2) - FK ke tabel users bawaan Jetstream
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            $table->string('name', 100); // Cth: BCA P1, Mandiri P2, Kas Bersama
            // Tipe: personal (pribadi), joint (bersama)
            $table->enum('type', ['personal', 'joint']); 
            // Saldo saat ini. Decimal untuk akurasi mata uang.
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->timestamps();

            // Unique constraint agar tidak ada akun dengan nama sama di user yang sama
            $table->unique(['user_id', 'name'], 'unique_user_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
