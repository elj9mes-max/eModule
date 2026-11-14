<?php

use App\Enums\ModuleStatus;
use App\Enums\QuizType;
use App\Models\Classroom;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('student progress page supplies completedQuizIds and completedLectureIds', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Adventure Quest',
        'slug' => 'adventure-quest',
    ]);

    $lecture = Lecture::factory()->for($module)->create([
        'title' => 'Chapter 1: The Beginning',
        'slug' => 'chapter-1-the-beginning',
        'sort_order' => 1,
    ]);

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Chapter 1 Quiz',
        'slug' => 'chapter-1-quiz',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'sort_order' => 2,
        'is_published' => true,
    ]);

    // Student completes the quiz
    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'score' => 10,
        'max_score' => 10,
        'percentage' => 100,
        'stars' => 3,
        'is_passed' => true,
        'xp_earned' => 150,
        'time_spent_seconds' => 45,
    ]);

    // Student visits progress page
    $response = $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds', [$quiz->id])
            ->where('completedLectureIds', [])
        );
});

test('student can complete lecture and have it recorded in database', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create();

    $response = $this->actingAs($student)
        ->postJson(route('students.lectures.complete', ['module' => $module->slug, 'lecture' => $lecture->slug]));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('student_lecture_completions', [
        'user_id' => $student->id,
        'lecture_id' => $lecture->id,
    ]);
});

test('classroom module cards report the student\'s real quest progress', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $classroom->enrollStudent($student->id);

    $module = Module::factory()->for($teacher, 'teacher')->create(['status' => ModuleStatus::Published]);
    $classroom->assignModule($module->id);

    $lecture = Lecture::factory()->for($module)->create(['sort_order' => 1]);
    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Progress Quiz',
        'slug' => 'progress-quiz',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'sort_order' => 2,
        'is_published' => true,
    ]);

    $this->actingAs($student)->postJson(route('students.lectures.complete', ['module' => $module->slug, 'lecture' => $lecture->slug]));

    $this->actingAs($student)
        ->get(route('students.classes.show', $classroom))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('modules.0.progress.completed', 1)
            ->where('modules.0.progress.total', 2)
            ->where('modules.0.progress.percent', 50)
        );

    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'stars' => 3,
        'xp_earned' => 100,
        'is_passed' => true,
    ]);

    $this->actingAs($student)
        ->get(route('students.classes.show', $classroom))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('modules.0.progress.completed', 2)
            ->where('modules.0.progress.percent', 100)
        );
});
