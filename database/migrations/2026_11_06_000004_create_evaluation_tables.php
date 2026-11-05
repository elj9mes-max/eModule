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
        Schema::create('evaluation_word_helpers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('word', 100);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('section_title', 255);
            $table->string('conflict_type', 100)->nullable();
            $table->text('question_prompt');
            $table->string('step1_prompt', 255)->default('Step 1: Pick one option:');
            $table->text('sentence_template');
            $table->text('hint')->nullable();
            $table->unsignedInteger('points')->default(25);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('evaluation_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('evaluation_items')->cascadeOnDelete();
            $table->string('option_text', 255);
            $table->boolean('is_correct')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['item_id', 'sort_order']);
        });

        Schema::create('evaluation_item_blanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('evaluation_items')->cascadeOnDelete();
            $table->unsignedInteger('blank_index')->default(1);
            $table->string('hint_label', 150)->nullable();
            $table->text('acceptable_answers')->nullable();
            $table->string('blank_size', 20)->default('short');
            $table->timestamps();

            $table->index(['item_id', 'blank_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_item_blanks');
        Schema::dropIfExists('evaluation_item_options');
        Schema::dropIfExists('evaluation_items');
        Schema::dropIfExists('evaluation_word_helpers');
    }
};
