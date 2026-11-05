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
        Schema::create('matching_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('choice_letter', 10);
            $table->text('choice_text');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('matching_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('correct_choice_id')->constrained('matching_choices')->cascadeOnDelete();
            $table->unsignedInteger('item_number')->default(1);
            $table->text('description');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('points')->default(10);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
            $table->index(['quiz_id', 'correct_choice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matching_items');
        Schema::dropIfExists('matching_choices');
    }
};
