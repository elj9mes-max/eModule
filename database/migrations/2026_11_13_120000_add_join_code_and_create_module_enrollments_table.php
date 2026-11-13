<?php

use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add unique 6-digit join code to modules
        Schema::table('modules', function (Blueprint $table): void {
            $table->string('join_code', 6)->nullable()->after('slug');
        });

        // Backfill 6-digit numerical codes for all existing modules
        $existingModules = DB::table('modules')->get(['id']);
        $assignedCodes = [];

        foreach ($existingModules as $m) {
            do {
                $code = (string) random_int(100000, 999999);
            } while (in_array($code, $assignedCodes, true) || DB::table('modules')->where('join_code', $code)->exists());

            $assignedCodes[] = $code;
            DB::table('modules')->where('id', $m->id)->update(['join_code' => $code]);
        }

        // Add unique index on join_code
        Schema::table('modules', function (Blueprint $table): void {
            $table->unique('join_code');
        });

        // 2. Create module enrollments pivot table
        Schema::create('module_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['module_id', 'user_id']);
            $table->index(['user_id', 'enrolled_at']);
        });

        // 3. Backfill enrollments for existing students who already have attempts or completions
        $lectureCompletions = StudentLectureCompletion::query()
            ->join('lectures', 'lectures.id', '=', 'student_lecture_completions.lecture_id')
            ->select(['lectures.module_id', 'student_lecture_completions.user_id', 'student_lecture_completions.completed_at'])
            ->get();

        foreach ($lectureCompletions as $lc) {
            DB::table('module_enrollments')->updateOrInsert(
                ['module_id' => $lc->module_id, 'user_id' => $lc->user_id],
                ['enrolled_at' => $lc->completed_at ?? now(), 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $quizAttempts = QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->select(['quizzes.module_id', 'quiz_attempts.user_id', 'quiz_attempts.created_at'])
            ->get();

        foreach ($quizAttempts as $qa) {
            DB::table('module_enrollments')->updateOrInsert(
                ['module_id' => $qa->module_id, 'user_id' => $qa->user_id],
                ['enrolled_at' => $qa->created_at ?? now(), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_enrollments');

        Schema::table('modules', function (Blueprint $table): void {
            $table->dropUnique(['join_code']);
            $table->dropColumn('join_code');
        });
    }
};
