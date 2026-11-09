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
        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('status', 30)->default('in_progress'); // in_progress, completed, abandoned
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->unsignedTinyInteger('stars')->default(0); // 0 to 3
            $table->boolean('is_passed')->default(false);
            $table->unsignedSmallInteger('xp_earned')->default(0);
            $table->unsignedMediumInteger('time_spent_seconds')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'quiz_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->string('item_type', 40); // multiple_choice, color_conflict, conflict_map, matching, evaluation, reflection
            $table->unsignedBigInteger('item_id');
            $table->json('answer_data');
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedSmallInteger('points_earned')->default(0);
            $table->unsignedSmallInteger('points_possible')->default(1);
            $table->text('teacher_notes')->nullable();
            $table->timestamps();

            $table->index(['attempt_id', 'item_type']);
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
    }
};
