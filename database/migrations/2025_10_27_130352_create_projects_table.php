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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // Kategori induk (misal: Persiapan Nikah, Tabungan Rumah)
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict'); 
            $table->string('name', 150);
            $table->decimal('target_total_amount', 15, 2)->nullable();
            $table->date('target_completion_date')->nullable();
            $table->enum('status', ['planning', 'on_going', 'completed', 'cancelled'])->default('planning');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
