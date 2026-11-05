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
        Schema::create('color_conflict_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('excerpt');
            $table->string('conflict_type');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->text('feedback_incorrect')->nullable();
            $table->unsignedInteger('points')->default(10);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
            $table->index(['quiz_id', 'conflict_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('color_conflict_cards');
    }
};
