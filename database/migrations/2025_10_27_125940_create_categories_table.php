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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // Nama kategori, cth: Gaji, Belanja Bulanan, Tabungan Rumah
            $table->string('name', 100);
            
            // Tipe: personal (hanya user ini) atau joint (bisa dilihat pasangan)
            $table->enum('type', ['personal', 'joint'])->default('personal');
            
            // Anggaran: income, expense, savings
            $table->enum('budget_type', ['income', 'expense', 'savings']);
            
            // User yang membuat kategori
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: nama + user_id untuk menghindari duplikasi
            $table->unique(['name', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
