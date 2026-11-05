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
        Schema::create('reflection_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('prompt_text');
            $table->unsignedInteger('max_words')->default(150);
            $table->unsignedInteger('min_words')->nullable();
            $table->text('guiding_hint')->nullable();
            $table->text('starter_sentence')->nullable();
            $table->unsignedInteger('points')->default(50);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reflection_prompts');
    }
};
