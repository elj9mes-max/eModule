<?php

use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    // A Wednesday, so "this week" started on Monday 2026-11-16.
    $this->travelTo('2026-11-18 10:00:00');

    $this->teacher = User::factory()->teacher()->create();
    $this->classroom = Classroom::factory()->create(['user_id' => $this->teacher->id]);
});

function enrolledStudent(Classroom $classroom, int $totalXp = 0): User
{
    $student = User::factory()->student()->create();
    $classroom->enrollStudent($student->id);
    StudentProfile::updateOrCreate(['user_id' => $student->id], ['total_xp' => $totalXp]);

    return $student;
}

function earnStars(User $student, int $stars, ?CarbonInterface $completedAt = null, string $status = 'completed'): void
{
    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => Quiz::factory()->create()->id,
        'attempt_number' => 1,
        'status' => $status,
        'score' => $stars,
        'max_score' => 10,
        'percentage' => $stars * 10,
        'stars' => $stars,
        'is_passed' => true,
        'xp_earned' => 0,
        'started_at' => $completedAt ?? now(),
        'completed_at' => $completedAt ?? now(),
    ]);
}

test('leaderboard ranks students by completed-quiz stars earned this week only', function () {
    $steady = enrolledStudent($this->classroom, totalXp: 5000);
    $sharp = enrolledStudent($this->classroom, totalXp: 100);
    $lastWeekStar = enrolledStudent($this->classroom, totalXp: 9000);

    earnStars($steady, 3);
    earnStars($steady, 6, status: 'pending_review');
    earnStars($sharp, 5, now()->startOfWeek());
    earnStars($sharp, 3);
    earnStars($lastWeekStar, 20, now()->startOfWeek()->subSecond());

    $this->actingAs($lastWeekStar)
        ->get(route('students.ranking', ['class_id' => $this->classroom->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Ranking')
            ->has('classmates', 2)
            ->where('classmates.0.id', $sharp->id)
            ->where('classmates.0.weekly_stars', 8)
            ->where('classmates.1.id', $steady->id)
            ->where('classmates.1.weekly_stars', 3)
            ->where('classmatesCount', 3)
            ->where('me.in_top', false)
            ->where('me.rank', null)
            ->where('me.weekly_stars', 0)
            ->where('me.stars_to_top', 1)
            ->where('week_resets_at', now()->startOfWeek()->addWeek()->toIso8601String())
        );
});

test('students tied on weekly stars are ordered by all-time XP', function () {
    $newcomer = enrolledStudent($this->classroom, totalXp: 200);
    $veteran = enrolledStudent($this->classroom, totalXp: 3000);

    earnStars($newcomer, 4);
    earnStars($veteran, 4);

    $this->actingAs($newcomer)
        ->get(route('students.ranking', ['class_id' => $this->classroom->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('classmates.0.id', $veteran->id)
            ->where('classmates.1.id', $newcomer->id)
            ->where('me.rank', 2)
            ->where('me.in_top', true)
            ->where('me.stars_to_top', 0)
        );
});

test('leaderboard resets when a new week starts', function () {
    $student = enrolledStudent($this->classroom);
    earnStars($student, 7);

    $this->actingAs($student)
        ->get(route('students.ranking', ['class_id' => $this->classroom->id]))
        ->assertInertia(fn (Assert $page) => $page->has('classmates', 1));

    $this->travelTo('2026-11-23 00:00:01');

    $this->actingAs($student)
        ->get(route('students.ranking', ['class_id' => $this->classroom->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classmates', 0)
            ->where('me.weekly_stars', 0)
            ->where('me.in_top', false)
        );
});

test('teacher roster ranks every student by this week\'s stars, keeping zero-star students listed', function () {
    $idle = enrolledStudent($this->classroom, totalXp: 9000);
    $lastWeekStar = enrolledStudent($this->classroom, totalXp: 100);
    $weeklyStar = enrolledStudent($this->classroom, totalXp: 50);

    earnStars($lastWeekStar, 30, now()->startOfWeek()->subDay());
    earnStars($weeklyStar, 4);

    $this->actingAs($this->teacher)
        ->get(route('teachers.students', ['class_id' => $this->classroom->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Students')
            ->has('students', 3)
            ->where('students.0.id', $weeklyStar->id)
            ->where('students.0.weekly_stars', 4)
            ->where('students.1.id', $idle->id)
            ->where('students.2.id', $lastWeekStar->id)
            ->where('students.2.weekly_stars', 0)
            ->where('week_resets_at', now()->startOfWeek()->addWeek()->toIso8601String())
        );
});

test('a student below a full top five is told how many stars pass fifth place', function () {
    foreach ([10, 9, 8, 7, 6] as $stars) {
        earnStars(enrolledStudent($this->classroom), $stars);
    }

    $student = enrolledStudent($this->classroom, totalXp: 9999);
    earnStars($student, 2);

    $this->actingAs($student)
        ->get(route('students.ranking', ['class_id' => $this->classroom->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classmates', 5)
            ->missing('classmates.0.email')
            ->where('me.rank', null)
            ->where('me.weekly_stars', 2)
            ->where('me.stars_to_top', 5)
        );
});
