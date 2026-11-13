<?php

use App\Enums\UserRole;
use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use App\Models\User;
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
        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('total_stars')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedSmallInteger('quizzes_passed_count')->default(0);
            $table->unsignedSmallInteger('lectures_completed_count')->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index(['total_xp', 'total_stars'], 'idx_student_leaderboard');
        });

        // Backfill profiles for any existing student users
        $studentIds = User::query()
            ->where('role', UserRole::Student)
            ->pluck('id');

        foreach ($studentIds as $userId) {
            // Lecture completions high-water mark
            $lectureXp = (int) StudentLectureCompletion::query()
                ->where('user_id', $userId)
                ->sum('xp_earned');

            $lecturesCount = (int) StudentLectureCompletion::query()
                ->where('user_id', $userId)
                ->count();

            // Quiz attempts high-water mark: best score per distinct quiz
            $quizStats = QuizAttempt::query()
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->groupBy('quiz_id')
                ->selectRaw('quiz_id, MAX(stars) as max_stars, MAX(xp_earned) as max_xp, MAX(is_passed) as passed')
                ->get();

            $quizStars = (int) $quizStats->sum('max_stars');
            $quizXp = (int) $quizStats->sum('max_xp');
            $quizzesPassed = (int) $quizStats->filter(fn ($stat) => (bool) $stat->passed)->count();

            $totalXp = $lectureXp + $quizXp;
            $level = $this->calculateInitialLevel($totalXp);

            DB::table('student_profiles')->insert([
                'user_id' => $userId,
                'total_xp' => $totalXp,
                'total_stars' => $quizStars,
                'level' => $level,
                'quizzes_passed_count' => $quizzesPassed,
                'lectures_completed_count' => $lecturesCount,
                'last_active_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Compute initial level based on progressive XP thresholds.
     */
    private function calculateInitialLevel(int $totalXp): int
    {
        $thresholds = [
            1 => 0,
            2 => 200,
            3 => 500,
            4 => 900,
            5 => 1400,
            6 => 2000,
            7 => 2700,
            8 => 3500,
            9 => 4400,
            10 => 5400,
        ];

        $currentLevel = 1;
        foreach ($thresholds as $lvl => $minXp) {
            if ($totalXp >= $minXp) {
                $currentLevel = $lvl;
            } else {
                break;
            }
        }

        if ($totalXp >= 5400) {
            $overflow = $totalXp - 5400;
            $currentLevel = 10 + (int) floor($overflow / 1000);
        }

        return $currentLevel;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
