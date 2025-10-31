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
        Schema::create('project_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            
            $table->enum('item_type', ['goods', 'service', 'document', 'task', 'material']); 
            $table->string('item_category', 150)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            
            $table->decimal('planned_amount', 15, 2)->nullable(); 
            $table->decimal('actual_spent', 15, 2)->default(0.00); 
            
            $table->enum('status', ['needed', 'in_progress', 'ready', 'complete', 'cancelled'])->default('needed');
            
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_items');
    }
};
