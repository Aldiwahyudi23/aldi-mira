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
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            // FK ke user yang bertanggung jawab atas utang
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); 
            $table->string('name', 150);
            $table->decimal('initial_amount', 15, 2);
            $table->decimal('remaining_balance', 15, 2); // Sisa utang yang harus dibayar
            $table->date('due_date')->nullable();
            $table->string('payment_frequency', 50)->nullable(); // Cth: monthly
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liabilities');
    }
};
