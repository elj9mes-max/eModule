<?php

use App\Enums\QuizType;
use App\Models\EvaluationItem;
use App\Models\EvaluationItemOption;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\ReflectionPrompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('teacher can view the grading dashboard with pending review attempts', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Reflection on Loyalty',
        'slug' => 'reflection-on-loyalty',
        'type' => QuizType::Reflection,
        'passing_percentage' => 70,
        'xp_reward' => 150,
        'is_published' => true,
    ]);

    $prompt = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'What does loyalty mean to you in the context of the story?',
        'min_words' => 5,
        'points' => 10,
        'sort_order' => 1,
    ]);

    $attempt = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'pending_review',
        'score' => 0,
        'max_score' => 10,
        'percentage' => 0,
        'stars' => 0,
        'is_passed' => false,
        'xp_earned' => 0,
        'time_spent_seconds' => 45,
    ]);

    QuizAttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'item_type' => 'reflection',
        'item_id' => $prompt->id,
        'answer_data' => ['text' => 'Loyalty means standing by friends when times are hard.', 'word_count' => 10],
        'answer_text' => 'Loyalty means standing by friends when times are hard.',
        'is_correct' => null,
        'points_earned' => 0,
        'points_possible' => 10,
    ]);

    $response = $this->actingAs($teacher)->get(route('teachers.grading.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Grading')
            ->has('submissions', 1)
            ->where('submissions.0.id', $attempt->id)
            ->where('submissions.0.status', 'pending_review')
            ->where('submissions.0.student.name', $student->name)
            ->where('stats.pending_count', 1)
        );
});

test('teacher can grade an evaluation quiz submission and update status to completed', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Evaluation of Community Action',
        'slug' => 'evaluation-community-action',
        'type' => QuizType::Evaluation,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    $item = EvaluationItem::create([
        'quiz_id' => $quiz->id,
        'section_title' => 'The Brave Decision',
        'conflict_type' => 'character_vs_nature',
        'question_prompt' => 'Evaluate whether the captain should have turned the ship around.',
        'step1_prompt' => 'Pick stance:',
        'sentence_template' => 'The captain was [stance] because [blank] and showed [blank].',
        'points' => 20,
        'sort_order' => 1,
    ]);

    $option = EvaluationItemOption::create([
        'item_id' => $item->id,
        'option_text' => 'Right Decision',
        'stance_value' => 'right',
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    $attempt = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'pending_review',
        'score' => 0,
        'max_score' => 20,
        'percentage' => 0,
        'stars' => 0,
        'is_passed' => false,
        'xp_earned' => 0,
        'time_spent_seconds' => 60,
    ]);

    $answer = QuizAttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'item_type' => 'evaluation',
        'item_id' => $item->id,
        'answer_data' => [
            'option_id' => $option->id,
            'stance' => 'right',
            'blanks' => ['he prioritized crew safety', 'true leadership'],
        ],
        'answer_text' => 'he prioritized crew safety | true leadership',
        'is_correct' => null,
        'points_earned' => 0,
        'points_possible' => 20,
    ]);

    $payload = [
        'answers' => [
            [
                'id' => $answer->id,
                'points_earned' => 18,
                'teacher_notes' => 'Clear reasoning and strong vocabulary.',
            ],
        ],
        'teacher_feedback' => 'Splendid work! You defended your position with sound evidence.',
    ];

    $response = $this->actingAs($teacher)
        ->postJson(route('teachers.grading.grade', ['attempt' => $attempt->id]), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'status' => 'completed',
                'score' => 18,
                'max_score' => 20,
                'percentage' => 90,
                'stars' => 3,
                'is_passed' => true,
                'teacher_feedback' => 'Splendid work! You defended your position with sound evidence.',
            ],
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $attempt->id,
        'status' => 'completed',
        'score' => 18,
        'stars' => 3,
        'is_passed' => true,
        'teacher_feedback' => 'Splendid work! You defended your position with sound evidence.',
    ]);

    $this->assertDatabaseHas('quiz_attempt_answers', [
        'id' => $answer->id,
        'points_earned' => 18,
        'is_correct' => true,
        'teacher_notes' => 'Clear reasoning and strong vocabulary.',
    ]);

    // Student viewing the quiz page now sees the completed attempt and teacher feedback
    $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->where('existingAttempt.status', 'completed')
            ->where('existingAttempt.score', 18)
            ->where('existingAttempt.stars', 3)
            ->where('existingAttempt.teacher_feedback', 'Splendid work! You defended your position with sound evidence.')
        );
});

test('teacher cannot grade submissions from other teachers modules', function () {
    $teacher1 = User::factory()->teacher()->create();
    $teacher2 = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher1, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Module 1 Quiz',
        'slug' => 'module-1-quiz',
        'type' => QuizType::Reflection,
        'passing_percentage' => 70,
        'xp_reward' => 100,
        'is_published' => true,
    ]);

    $attempt = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'pending_review',
        'score' => 0,
        'max_score' => 10,
    ]);

    $answer = QuizAttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'item_type' => 'reflection',
        'item_id' => 1,
        'answer_data' => ['text' => 'Some reflection text here.'],
        'points_possible' => 10,
    ]);

    // Teacher 2 tries to grade Teacher 1's quiz attempt
    $response = $this->actingAs($teacher2)
        ->postJson(route('teachers.grading.grade', ['attempt' => $attempt->id]), [
            'answers' => [
                ['id' => $answer->id, 'points_earned' => 10],
            ],
        ]);

    $response->assertStatus(403);
});
