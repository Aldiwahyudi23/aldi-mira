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
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->foreignId('related_person_id')->constrained('persons')->onDelete('cascade');
            $table->enum('type', ['parent', 'child', 'spouse', 'ex_spouse']);
            $table->foreignId('marriage_id')->nullable()->constrained('marriages')->onDelete('set null');
            $table->timestamps();

            $table->unique(['person_id', 'related_person_id', 'type']);
            $table->index(['person_id', 'related_person_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
