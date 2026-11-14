<?php

use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\ModuleBonusClaim;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Gamification\LevelProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $teacher = User::factory()->teacher()->create();

    $this->student = User::factory()->student()->create();
    $this->module = Module::factory()->for($teacher, 'teacher')->create(['xp_bonus' => 300]);
    $this->lecture = Lecture::factory()->for($this->module)->create(['sort_order' => 1]);
    $this->quiz = Quiz::create([
        'module_id' => $this->module->id,
        'title' => 'Vault Gate Quiz',
        'slug' => 'vault-gate-quiz',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'sort_order' => 2,
        'is_published' => true,
    ]);
});

/**
 * Mark every lecture and quiz in the module as finished for the student.
 */
function completeEveryQuest(User $student, Lecture $lecture, Quiz $quiz): void
{
    StudentLectureCompletion::create([
        'user_id' => $student->id,
        'lecture_id' => $lecture->id,
        'xp_earned' => 0,
        'completed_at' => now(),
    ]);

    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'stars' => 3,
        'xp_earned' => 0,
        'is_passed' => true,
    ]);
}

test('student who finished every quest claims the vault bonus once', function (): void {
    completeEveryQuest($this->student, $this->lecture, $this->quiz);

    $this->actingAs($this->student)
        ->postJson(route('students.modules.bonus.claim', ['module' => $this->module->slug]))
        ->assertOk()
        ->assertJsonPath('claim.xp_awarded', 300)
        ->assertJsonPath('claim.xp_delta', 300)
        ->assertJsonPath('claim.already_claimed', false);

    expect(StudentProfile::find($this->student->id)->total_xp)->toBe(300);
    expect(ModuleBonusClaim::where('user_id', $this->student->id)->count())->toBe(1);
});

test('claiming the vault bonus again never awards more xp', function (): void {
    completeEveryQuest($this->student, $this->lecture, $this->quiz);

    $this->actingAs($this->student)
        ->postJson(route('students.modules.bonus.claim', ['module' => $this->module->slug]))
        ->assertOk();

    $this->actingAs($this->student)
        ->postJson(route('students.modules.bonus.claim', ['module' => $this->module->slug]))
        ->assertOk()
        ->assertJsonPath('claim.already_claimed', true)
        ->assertJsonPath('claim.xp_delta', 0)
        ->assertJsonPath('claim.xp_awarded', 300);

    expect(StudentProfile::find($this->student->id)->total_xp)->toBe(300);
    expect(ModuleBonusClaim::where('user_id', $this->student->id)->count())->toBe(1);
});

test('vault bonus stays locked until every lecture and quiz is complete', function (): void {
    StudentLectureCompletion::create([
        'user_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'xp_earned' => 0,
        'completed_at' => now(),
    ]);

    $this->actingAs($this->student)
        ->postJson(route('students.modules.bonus.claim', ['module' => $this->module->slug]))
        ->assertUnprocessable();

    expect(ModuleBonusClaim::count())->toBe(0);
});

test('teachers cannot claim a vault bonus', function (): void {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->postJson(route('students.modules.bonus.claim', ['module' => $this->module->slug]))
        ->assertForbidden();

    expect(ModuleBonusClaim::count())->toBe(0);
});

test('progress page tells the student whether the vault bonus was claimed', function (): void {
    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('bonusClaim.claimed', false)
            ->where('bonusClaim.xp_awarded', null)
        );

    ModuleBonusClaim::factory()->create([
        'user_id' => $this->student->id,
        'module_id' => $this->module->id,
        'xp_awarded' => 300,
    ]);

    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('bonusClaim.claimed', true)
            ->where('bonusClaim.xp_awarded', 300)
        );
});

test('profile recalculation keeps claimed vault bonus xp', function (): void {
    ModuleBonusClaim::factory()->create([
        'user_id' => $this->student->id,
        'module_id' => $this->module->id,
        'xp_awarded' => 300,
    ]);

    $profile = app(LevelProgressionService::class)->recalculateStudentProfile($this->student->id);

    expect($profile->total_xp)->toBe(300);
});
