<?php

use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use App\Services\Gamification\LevelProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->progressionService = app(LevelProgressionService::class);
    $this->cache = app(EModuleCacheService::class);
});

test('calculates progressive levels correctly across thresholds', function (): void {
    expect($this->progressionService->calculateLevel(0))->toBe(1);
    expect($this->progressionService->calculateLevel(199))->toBe(1);
    expect($this->progressionService->calculateLevel(200))->toBe(2);
    expect($this->progressionService->calculateLevel(499))->toBe(2);
    expect($this->progressionService->calculateLevel(500))->toBe(3);
    expect($this->progressionService->calculateLevel(1400))->toBe(5);
    expect($this->progressionService->calculateLevel(5400))->toBe(10);
    expect($this->progressionService->calculateLevel(6400))->toBe(11);
    expect($this->progressionService->calculateLevel(7400))->toBe(12);
});

test('returns rich level progression details for HUD XP bar', function (): void {
    $details = $this->progressionService->getLevelDetails(250);

    expect($details['level'])->toBe(2);
    expect($details['title'])->toBe('Trail Scout');
    expect($details['badge'])->toBe('🧭 Scout');
    expect($details['current_level_min_xp'])->toBe(200);
    expect($details['next_level_xp'])->toBe(500);
    expect($details['xp_in_level'])->toBe(50);
    expect($details['xp_for_next_level'])->toBe(300);
    expect($details['progress_pct'])->toBe(17);
});

test('records lecture completion and prevents duplicate XP farming', function (): void {
    $teacher = User::factory()->teacher()->create();
    $module = Module::create([
        'user_id' => $teacher->id,
        'title' => 'Story Quest',
        'slug' => 'story-quest',
        'grade_level' => 'Grade 4',
    ]);
    $lecture = Lecture::create([
        'module_id' => $module->id,
        'title' => 'Chapter 1: The Trail',
        'slug' => 'chapter-1-the-trail',
        'xp_reward' => 120,
    ]);
    $student = User::factory()->student()->create();

    // First completion
    $result1 = $this->progressionService->recordLectureCompletion($student, $lecture, 120);

    expect($result1['xp_earned'])->toBe(120);
    expect($result1['xp_delta'])->toBe(120);
    expect($result1['total_xp'])->toBe(120);
    expect($result1['is_first_completion'])->toBeTrue();

    $profile = StudentProfile::find($student->id);
    expect($profile->total_xp)->toBe(120);
    expect($profile->lectures_completed_count)->toBe(1);

    // Repeated completion attempt (must not duplicate XP)
    $result2 = $this->progressionService->recordLectureCompletion($student, $lecture, 120);

    expect($result2['xp_delta'])->toBe(0);
    expect($result2['total_xp'])->toBe(120);
    expect($result2['is_first_completion'])->toBeFalse();

    $profile->refresh();
    expect($profile->total_xp)->toBe(120);
    expect($profile->lectures_completed_count)->toBe(1);
});

test('enforces high-water mark rule for stars and XP on quiz attempts', function (): void {
    $teacher = User::factory()->teacher()->create();
    $module = Module::create([
        'user_id' => $teacher->id,
        'title' => 'Story Arena',
        'slug' => 'story-arena',
        'grade_level' => 'Grade 4',
    ]);
    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Rune Quiz 1',
        'slug' => 'rune-quiz-1',
        'type' => 'multiple_choice',
        'xp_reward' => 200,
    ]);
    $student = User::factory()->student()->create();

    // Attempt 1: 1 Star, 80 XP
    $attempt1 = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'stars' => 1,
        'xp_earned' => 80,
        'is_passed' => true,
    ]);
    $res1 = $this->progressionService->recordQuizAttempt($student, $quiz, 1, 80, true, excludeAttemptId: $attempt1->id);

    expect($res1['star_delta'])->toBe(1);
    expect($res1['xp_delta'])->toBe(80);
    expect($res1['total_stars'])->toBe(1);
    expect($res1['total_xp'])->toBe(80);
    expect($res1['is_first_pass'])->toBeTrue();

    // Attempt 2: Worse score (0 stars, 30 XP) -> must grant 0 deltas
    $attempt2 = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'stars' => 0,
        'xp_earned' => 30,
        'is_passed' => false,
    ]);
    $res2 = $this->progressionService->recordQuizAttempt($student, $quiz, 0, 30, false, excludeAttemptId: $attempt2->id);

    expect($res2['star_delta'])->toBe(0);
    expect($res2['xp_delta'])->toBe(0);
    expect($res2['total_stars'])->toBe(1);
    expect($res2['total_xp'])->toBe(80);
    expect($res2['is_first_pass'])->toBeFalse();

    // Attempt 3: Perfect score (3 stars, 200 XP) -> delta is +2 stars (3 - 1) and +120 XP (200 - 80)
    $attempt3 = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 3,
        'status' => 'completed',
        'stars' => 3,
        'xp_earned' => 200,
        'is_passed' => true,
    ]);
    $res3 = $this->progressionService->recordQuizAttempt($student, $quiz, 3, 200, true, excludeAttemptId: $attempt3->id);

    expect($res3['star_delta'])->toBe(2);
    expect($res3['xp_delta'])->toBe(120);
    expect($res3['total_stars'])->toBe(3);
    expect($res3['total_xp'])->toBe(200); // 80 + 120 = 200 total!
    expect($res3['level'])->toBe(2); // 200 XP reached Level 2!
    expect($res3['leveled_up'])->toBeTrue();
});

test('self-healing recalculation reconciles student profile accurately', function (): void {
    $teacher = User::factory()->teacher()->create();
    $module = Module::create([
        'user_id' => $teacher->id,
        'title' => 'Recon Quest',
        'slug' => 'recon-quest',
        'grade_level' => 'Grade 4',
    ]);
    $lecture = Lecture::create([
        'module_id' => $module->id,
        'title' => 'Intro Chapter',
        'slug' => 'intro-chapter',
        'xp_reward' => 100,
    ]);
    $quiz1 = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Quiz One',
        'slug' => 'quiz-one',
        'type' => 'multiple_choice',
        'xp_reward' => 150,
    ]);
    $quiz2 = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Quiz Two',
        'slug' => 'quiz-two',
        'type' => 'matching_type',
        'xp_reward' => 200,
    ]);

    $student = User::factory()->student()->create();

    // 1 lecture completed (100 XP)
    StudentLectureCompletion::create([
        'user_id' => $student->id,
        'lecture_id' => $lecture->id,
        'xp_earned' => 100,
    ]);

    // Quiz 1: 2 attempts, best is 2 stars, 120 XP
    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz1->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'stars' => 1,
        'xp_earned' => 75,
        'is_passed' => true,
    ]);
    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz1->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'stars' => 2,
        'xp_earned' => 120,
        'is_passed' => true,
    ]);

    // Quiz 2: 1 attempt, 3 stars, 200 XP
    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz2->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'stars' => 3,
        'xp_earned' => 200,
        'is_passed' => true,
    ]);

    $profile = $this->progressionService->recalculateStudentProfile($student->id);

    // Expected: 100 (lecture) + 120 (quiz 1 best) + 200 (quiz 2 best) = 420 XP
    // Expected Stars: 2 (quiz 1 best) + 3 (quiz 2 best) = 5 Stars
    expect($profile->total_xp)->toBe(420);
    expect($profile->total_stars)->toBe(5);
    expect($profile->quizzes_passed_count)->toBe(2);
    expect($profile->lectures_completed_count)->toBe(1);
    expect($profile->level)->toBe(2);
});

test('ranks students dynamically by total XP and total stars in leaderboard cache', function (): void {
    $studentA = User::factory()->student()->create(['name' => 'Alice']);
    $studentB = User::factory()->student()->create(['name' => 'Bob']);
    $studentC = User::factory()->student()->create(['name' => 'Charlie']);

    StudentProfile::where('user_id', $studentA->id)->update([
        'total_xp' => 1500,
        'total_stars' => 15,
        'level' => 5,
    ]);
    StudentProfile::where('user_id', $studentB->id)->update([
        'total_xp' => 2400,
        'total_stars' => 22,
        'level' => 6,
    ]);
    StudentProfile::where('user_id', $studentC->id)->update([
        'total_xp' => 800,
        'total_stars' => 8,
        'level' => 3,
    ]);

    $ranking = $this->cache->getStudentRanking($studentA->id);

    // Expected order: Bob (Rank 1), Alice (Rank 2), Charlie (Rank 3)
    expect($ranking)->toHaveCount(3);
    expect($ranking[0]['id'])->toBe($studentB->id);
    expect($ranking[0]['rank'])->toBe(1);
    expect($ranking[0]['badge'])->toBe('🥇 Champion');

    expect($ranking[1]['id'])->toBe($studentA->id);
    expect($ranking[1]['rank'])->toBe(2);
    expect($ranking[1]['is_current_user'])->toBeTrue();
    expect($ranking[1]['badge'])->toBe('🥈 Vanguard');

    expect($ranking[2]['id'])->toBe($studentC->id);
    expect($ranking[2]['rank'])->toBe(3);
    expect($ranking[2]['badge'])->toBe('🥉 Explorer');
});
