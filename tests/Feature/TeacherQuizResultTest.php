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

test('teacher can view quiz results inspector page with analytics and student attempts', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Coastal Defense Reflection',
        'slug' => 'coastal-defense-reflection',
        'type' => QuizType::Reflection,
        'passing_percentage' => 70,
        'xp_reward' => 150,
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $prompt = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'What did you discover about working together during the storm?',
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
        'answer_data' => ['text' => 'We discovered that unity makes every obstacle surmountable.', 'word_count' => 9],
        'answer_text' => 'We discovered that unity makes every obstacle surmountable.',
        'points_possible' => 10,
    ]);

    $response = $this->actingAs($teacher)
        ->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/view/quizes/QuizResult')
            ->where('quiz.title', 'Coastal Defense Reflection')
            ->where('analytics.total_attempts', 1)
            ->where('analytics.pending_review_count', 1)
            ->has('submissions', 1)
            ->where('submissions.0.id', $attempt->id)
            ->where('submissions.0.status', 'pending_review')
            ->where('submissions.0.student.name', $student->name)
        );
});

test('teacher can grade an evaluation quiz directly on the QuizResult page', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Evaluation of Character Choices',
        'slug' => 'evaluation-character-choices',
        'type' => QuizType::Evaluation,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'sort_order' => 2,
        'is_published' => true,
    ]);

    $item = EvaluationItem::create([
        'quiz_id' => $quiz->id,
        'section_title' => 'Decision Under Pressure',
        'conflict_type' => 'character_vs_society',
        'question_prompt' => 'Was Tomas right to share the harvest?',
        'step1_prompt' => 'Pick stance:',
        'sentence_template' => 'Tomas was [stance] because [blank] and showed [blank].',
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
    ]);

    $answer = QuizAttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'item_type' => 'evaluation',
        'item_id' => $item->id,
        'answer_data' => [
            'option_id' => $option->id,
            'stance' => 'right',
            'blanks' => ['he prevented famine', 'true generosity'],
        ],
        'answer_text' => 'he prevented famine | true generosity',
        'points_possible' => 20,
    ]);

    $payload = [
        'answers' => [
            [
                'id' => $answer->id,
                'points_earned' => 20,
                'teacher_notes' => 'Flawless reasoning and vocabulary choices!',
            ],
        ],
        'teacher_feedback' => 'Incredible analysis of the moral dilemma!',
    ];

    $response = $this->actingAs($teacher)
        ->postJson(route('teachers.quizzes.results.grade', [
            'module' => $module->slug,
            'quiz' => $quiz->slug,
            'attempt' => $attempt->id,
        ]), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'status' => 'completed',
                'score' => 20,
                'max_score' => 20,
                'percentage' => 100,
                'stars' => 3,
                'is_passed' => true,
                'teacher_feedback' => 'Incredible analysis of the moral dilemma!',
            ],
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $attempt->id,
        'status' => 'completed',
        'score' => 20,
        'percentage' => 100,
        'stars' => 3,
        'is_passed' => true,
    ]);
});

test('teacher can grade a reflection quiz directly on the QuizResult page', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Reflection on Community Kindness',
        'slug' => 'reflection-community-kindness',
        'type' => QuizType::Reflection,
        'passing_percentage' => 70,
        'xp_reward' => 150,
        'sort_order' => 3,
        'is_published' => true,
    ]);

    $prompt = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'How can you apply this chapter\'s lesson in your own life?',
        'min_words' => 5,
        'points' => 15,
        'sort_order' => 1,
    ]);

    $attempt = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'pending_review',
        'score' => 0,
        'max_score' => 15,
    ]);

    $answer = QuizAttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'item_type' => 'reflection',
        'item_id' => $prompt->id,
        'answer_data' => [
            'text' => 'I will listen more carefully to my classmates when working on group projects.',
            'word_count' => 13,
        ],
        'answer_text' => 'I will listen more carefully to my classmates when working on group projects.',
        'points_possible' => 15,
    ]);

    $payload = [
        'answers' => [
            [
                'id' => $answer->id,
                'points_earned' => 14,
                'teacher_notes' => 'Very thoughtful real-world application.',
            ],
        ],
        'teacher_feedback' => 'Wonderful reflection! Listening is indeed a cornerstone of good teamwork.',
    ];

    $response = $this->actingAs($teacher)
        ->postJson(route('teachers.quizzes.results.grade', [
            'module' => $module->slug,
            'quiz' => $quiz->slug,
            'attempt' => $attempt->id,
        ]), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'status' => 'completed',
                'score' => 14,
                'max_score' => 15,
                'stars' => 3,
                'is_passed' => true,
            ],
        ]);
});

test('unauthorized teacher cannot view or grade another teachers quiz results', function () {
    $teacher1 = User::factory()->teacher()->create();
    $teacher2 = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher1, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Secret Quiz',
        'slug' => 'secret-quiz',
        'type' => QuizType::MultipleChoice,
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $attempt = QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'pending_review',
        'max_score' => 10,
    ]);

    // Teacher 2 tries to view Teacher 1's quiz results
    $this->actingAs($teacher2)
        ->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertRedirect(route('teachers.dashboard'));

    // Teacher 2 tries to grade Teacher 1's quiz attempt
    $this->actingAs($teacher2)
        ->postJson(route('teachers.quizzes.results.grade', [
            'module' => $module->slug,
            'quiz' => $quiz->slug,
            'attempt' => $attempt->id,
        ]), [
            'answers' => [['id' => 1, 'points_earned' => 10]],
        ])
        ->assertStatus(403);
});

test('evaluation quiz submission records stance properly even when option stance_value is null', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Evaluation Stance Integrity Test',
        'slug' => 'evaluation-stance-integrity-test',
        'type' => QuizType::Evaluation,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $item = EvaluationItem::create([
        'quiz_id' => $quiz->id,
        'section_title' => 'Fairness Section',
        'question_prompt' => 'Did the chief make the right choice?',
        'step1_prompt' => 'Pick your stance:',
        'sentence_template' => 'The chief was [stance] because [blank].',
        'points' => 25,
        'sort_order' => 1,
    ]);

    // Option has option_text but stance_value is null (e.g. from user input)
    $option = EvaluationItemOption::create([
        'item_id' => $item->id,
        'option_text' => 'Right and Just',
        'stance_value' => null,
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    // Student submits quiz choosing option_id
    $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), [
            'answers' => [
                $item->id => [
                    'option_id' => $option->id,
                    'blanks' => ['he prioritized community harmony'],
                ],
            ],
            'time_spent_seconds' => 45,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'pending_review',
        ]);

    // Verify database record has stance populated with option_text
    $answerRecord = QuizAttemptAnswer::where('item_id', $item->id)->firstOrFail();
    expect($answerRecord->answer_data['option_id'])->toBe($option->id);
    expect($answerRecord->answer_data['stance'])->toBe('Right and Just');

    // Verify teacher viewing results sees the resolved stance in Inertia props
    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('teachers/view/quizes/QuizResult')
            ->has('submissions.0.answers.0', fn ($ans) => $ans
                ->where('answer_data.stance', 'Right and Just')
                ->where('answer_data.option_id', $option->id)
                ->etc()
            )
        );
});
