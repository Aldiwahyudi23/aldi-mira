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
        Schema::create('item_checklists', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke Item Proyek (Cth: Dokumen Nikah)
            $table->foreignId('project_item_id')->constrained('project_items')->onDelete('cascade');
            $table->string('task_description', 255);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_checklists');
    }
};
