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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('lecture_id')->nullable()->constrained('lectures')->nullOnDelete();
            $table->string('type')->default('multiple_choice');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->longText('reading_passage')->nullable();
            $table->unsignedInteger('xp_reward')->default(150);
            $table->unsignedSmallInteger('passing_percentage')->default(70);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'slug']);
            $table->index(['module_id', 'sort_order']);
            $table->index(['module_id', 'type']);
            $table->index(['module_id', 'is_published']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('selection_type')->default('single');
            $table->text('passage_context')->nullable();
            $table->string('image_path')->nullable();
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('points')->default(10);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->text('feedback')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
            $table->index(['question_id', 'is_correct']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_question_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
