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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            
            // Periode Anggaran (Bulan/Tahun)
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_amount', 15, 2);
            // Nilai ini dihitung dari akumulasi transaksi yang masuk ke category_id ini dalam periode ini
            $table->decimal('spent_amount', 15, 2)->default(0.00); 
            
            $table->timestamps();
        });
        
        // Memastikan hanya ada satu budget per kategori per periode
        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['category_id', 'period_start'], 'unique_category_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
