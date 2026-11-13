<?php

namespace App\Services\Gamification;

use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Support\Facades\DB;

class LevelProgressionService
{
    /**
     * Progressive level thresholds (Level => Minimum Total XP required).
     *
     * @var array<int, int>
     */
    private const LEVEL_THRESHOLDS = [
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

    /**
     * Flavor titles for each progressive level tier.
     *
     * @var array<int, array{title: string, badge: string}>
     */
    private const LEVEL_FLAVORS = [
        1 => ['title' => 'Novice Reader', 'badge' => '🌱 Sprout'],
        2 => ['title' => 'Trail Scout', 'badge' => '🧭 Scout'],
        3 => ['title' => 'Meadow Pathfinder', 'badge' => '🗺️ Explorer'],
        4 => ['title' => 'Forest Navigator', 'badge' => '🌲 Navigator'],
        5 => ['title' => 'Rune Scholar', 'badge' => '📜 Scholar'],
        6 => ['title' => 'Chronicle Seeker', 'badge' => '🔮 Seeker'],
        7 => ['title' => 'Lorekeeper', 'badge' => '🏛️ Lorekeeper'],
        8 => ['title' => 'Master Storyweaver', 'badge' => '✨ Storyweaver'],
        9 => ['title' => 'Legendary Pioneer', 'badge' => '⭐ Pioneer'],
        10 => ['title' => 'Grand Champion of Lore', 'badge' => '👑 Champion'],
    ];

    public function __construct(
        private readonly EModuleCacheService $cache,
    ) {}

    /**
     * Compute player level from total XP.
     */
    public function calculateLevel(int $totalXp): int
    {
        $safeXp = max(0, $totalXp);
        $level = 1;

        foreach (self::LEVEL_THRESHOLDS as $lvl => $minXp) {
            if ($safeXp >= $minXp) {
                $level = $lvl;
            } else {
                break;
            }
        }

        // Endless linear tiering beyond level 10 (+1,000 XP per level)
        if ($safeXp >= 5400) {
            $overflow = $safeXp - 5400;
            $level = 10 + (int) floor($overflow / 1000);
        }

        return $level;
    }

    /**
     * Get rich level progression details for the HUD XP bar.
     *
     * @return array{
     *     level: int,
     *     title: string,
     *     badge: string,
     *     current_level_min_xp: int,
     *     next_level_xp: int,
     *     xp_in_level: int,
     *     xp_for_next_level: int,
     *     progress_pct: int
     * }
     */
    public function getLevelDetails(int $totalXp): array
    {
        $safeXp = max(0, $totalXp);
        $level = $this->calculateLevel($safeXp);

        $flavor = self::LEVEL_FLAVORS[min(10, $level)] ?? ['title' => "Champion Tier {$level}", 'badge' => '👑 Champion'];

        if ($level < 10) {
            $currentMin = self::LEVEL_THRESHOLDS[$level];
            $nextMin = self::LEVEL_THRESHOLDS[$level + 1];
        } else {
            $levelsBeyondTen = $level - 10;
            $currentMin = 5400 + ($levelsBeyondTen * 1000);
            $nextMin = $currentMin + 1000;
        }

        $neededForLevel = max(1, $nextMin - $currentMin);
        $earnedInLevel = max(0, min($neededForLevel, $safeXp - $currentMin));
        $pct = (int) round(($earnedInLevel / $neededForLevel) * 100);

        return [
            'level' => $level,
            'title' => $flavor['title'],
            'badge' => $flavor['badge'],
            'current_level_min_xp' => $currentMin,
            'next_level_xp' => $nextMin,
            'xp_in_level' => $earnedInLevel,
            'xp_for_next_level' => $neededForLevel,
            'progress_pct' => min(100, max(0, $pct)),
        ];
    }

    /**
     * Atomically process quiz results, award high-water mark XP and stars, and update the profile.
     *
     * @return array{
     *     xp_earned: int,
     *     xp_delta: int,
     *     stars: int,
     *     star_delta: int,
     *     total_xp: int,
     *     total_stars: int,
     *     level: int,
     *     leveled_up: bool,
     *     is_first_pass: bool
     * }
     */
    public function recordQuizAttempt(
        User $student,
        Quiz $quiz,
        int $starsEarned,
        int $xpEarned,
        bool $isPassed,
        ?int $excludeAttemptId = null,
    ): array {
        return DB::transaction(function () use ($student, $quiz, $starsEarned, $xpEarned, $isPassed, $excludeAttemptId): array {
            $profile = StudentProfile::query()->lockForUpdate()->find($student->id);

            if (! $profile) {
                $profile = StudentProfile::create(['user_id' => $student->id]);
            }

            // High-water mark from prior completed attempts of this quiz
            $priorStats = QuizAttempt::query()
                ->where('user_id', $student->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->when($excludeAttemptId !== null, fn ($q) => $q->where('id', '!=', $excludeAttemptId))
                ->selectRaw('COALESCE(MAX(stars), 0) as max_stars, COALESCE(MAX(xp_earned), 0) as max_xp, MAX(is_passed) as had_passed')
                ->first();

            $prevMaxStars = (int) ($priorStats->max_stars ?? 0);
            $prevMaxXp = (int) ($priorStats->max_xp ?? 0);
            $hadPassed = (bool) ($priorStats->had_passed ?? false);

            $starDelta = max(0, $starsEarned - $prevMaxStars);
            $xpDelta = max(0, $xpEarned - $prevMaxXp);
            $isFirstPass = $isPassed && ! $hadPassed;

            $oldLevel = $profile->level;
            $newTotalXp = $profile->total_xp + $xpDelta;
            $newTotalStars = $profile->total_stars + $starDelta;
            $newLevel = $this->calculateLevel($newTotalXp);
            $leveledUp = $newLevel > $oldLevel;

            $profile->total_xp = $newTotalXp;
            $profile->total_stars = $newTotalStars;
            $profile->level = $newLevel;
            if ($isFirstPass) {
                $profile->quizzes_passed_count += 1;
            }
            $profile->last_active_at = now();
            $profile->save();

            $this->cache->invalidateRanking();

            return [
                'xp_earned' => $xpEarned,
                'xp_delta' => $xpDelta,
                'stars' => $starsEarned,
                'star_delta' => $starDelta,
                'total_xp' => $newTotalXp,
                'total_stars' => $newTotalStars,
                'level' => $newLevel,
                'leveled_up' => $leveledUp,
                'is_first_pass' => $isFirstPass,
            ];
        });
    }

    /**
     * Atomically process lecture completion and award completion XP.
     *
     * @return array{
     *     xp_earned: int,
     *     xp_delta: int,
     *     total_xp: int,
     *     level: int,
     *     leveled_up: bool,
     *     is_first_completion: bool
     * }
     */
    public function recordLectureCompletion(User $student, Lecture $lecture, int $xpEarned): array
    {
        return DB::transaction(function () use ($student, $lecture, $xpEarned): array {
            $profile = StudentProfile::query()->lockForUpdate()->find($student->id);

            if (! $profile) {
                $profile = StudentProfile::create(['user_id' => $student->id]);
            }

            $alreadyCompleted = StudentLectureCompletion::query()
                ->where('user_id', $student->id)
                ->where('lecture_id', $lecture->id)
                ->exists();

            $xpDelta = $alreadyCompleted ? 0 : max(0, $xpEarned);
            $oldLevel = $profile->level;
            $newTotalXp = $profile->total_xp + $xpDelta;
            $newLevel = $this->calculateLevel($newTotalXp);
            $leveledUp = $newLevel > $oldLevel;

            if (! $alreadyCompleted) {
                StudentLectureCompletion::create([
                    'user_id' => $student->id,
                    'lecture_id' => $lecture->id,
                    'xp_earned' => $xpEarned,
                    'completed_at' => now(),
                ]);

                $profile->total_xp = $newTotalXp;
                $profile->level = $newLevel;
                $profile->lectures_completed_count += 1;
                $profile->last_active_at = now();
                $profile->save();

                $this->cache->invalidateRanking();
            }

            return [
                'xp_earned' => $xpEarned,
                'xp_delta' => $xpDelta,
                'total_xp' => $newTotalXp,
                'level' => $newLevel,
                'leveled_up' => $leveledUp,
                'is_first_completion' => ! $alreadyCompleted,
            ];
        });
    }

    /**
     * Self-healing full recalculation of a student's high-water mark XP and Stars.
     */
    public function recalculateStudentProfile(int $userId): StudentProfile
    {
        return DB::transaction(function () use ($userId): StudentProfile {
            $lectureXp = (int) StudentLectureCompletion::query()
                ->where('user_id', $userId)
                ->sum('xp_earned');

            $lecturesCount = (int) StudentLectureCompletion::query()
                ->where('user_id', $userId)
                ->count();

            $quizStats = QuizAttempt::query()
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->groupBy('quiz_id')
                ->selectRaw('quiz_id, MAX(stars) as max_stars, MAX(xp_earned) as max_xp, MAX(is_passed) as passed')
                ->get();

            $quizStars = (int) $quizStats->sum('max_stars');
            $quizXp = (int) $quizStats->sum('max_xp');
            $quizzesPassed = (int) $quizStats->filter(fn ($s) => (bool) $s->passed)->count();

            $totalXp = $lectureXp + $quizXp;
            $level = $this->calculateLevel($totalXp);

            $profile = StudentProfile::query()->find($userId);

            if (! $profile) {
                $profile = new StudentProfile;
                $profile->user_id = $userId;
            }

            $profile->total_xp = $totalXp;
            $profile->total_stars = $quizStars;
            $profile->level = $level;
            $profile->quizzes_passed_count = $quizzesPassed;
            $profile->lectures_completed_count = $lecturesCount;
            $profile->last_active_at = now();
            $profile->save();

            $this->cache->invalidateRanking();

            return $profile;
        });
    }
}
