<?php

use App\Enums\QuestionSelectionType;
use App\Enums\QuizType;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('quiz view for students does not leak correct answers or explanations', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Secret Test Quiz',
        'slug' => 'secret-test-quiz',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'xp_reward' => 150,
        'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'What is the main conflict?',
        'selection_type' => QuestionSelectionType::Single,
        'explanation' => 'Super secret answer explanation that should never be sent',
        'points' => 10,
        'sort_order' => 0,
    ]);

    QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Correct Answer Option',
        'is_correct' => true,
        'sort_order' => 0,
    ]);

    QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Wrong Answer Option',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->missing('quiz.questions.0.explanation')
            ->missing('quiz.questions.0.options.0.is_correct')
            ->missing('quiz.questions.0.options.1.is_correct')
        );
});

test('student can submit quiz answers and have attempt and answers saved to database', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Multiple Choice Mastery',
        'slug' => 'multiple-choice-mastery',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Identify the conflict',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 0,
    ]);

    $optCorrect = QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Character vs Nature',
        'is_correct' => true,
        'sort_order' => 0,
    ]);

    $optWrong = QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Character vs Character',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $payload = [
        'time_spent_seconds' => 45,
        'answers' => [
            (string) $question->id => [$optCorrect->id],
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'attempt_number' => 1,
                'score' => 10,
                'max_score' => 10,
                'percentage' => 100.0,
                'stars' => 3,
                'is_passed' => true,
                'xp_earned' => 200,
                'time_spent_seconds' => 45,
            ],
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'score' => 10,
        'max_score' => 10,
        'stars' => 3,
        'is_passed' => true,
        'xp_earned' => 200,
        'time_spent_seconds' => 45,
    ]);

    $this->assertDatabaseHas('quiz_attempt_answers', [
        'item_type' => 'multiple_choice',
        'item_id' => $question->id,
        'is_correct' => true,
        'points_earned' => 10,
        'points_possible' => 10,
    ]);

    // Attempting to submit again should be rejected with 422
    $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $payload)
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'You have already completed this quiz.',
        ]);

    // Visiting the quiz page should now provide existingAttempt
    $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->where('existingAttempt.percentage', 100)
            ->where('existingAttempt.score', 10)
        );
});

test('quiz attempt with failing score marks quiz as completed in progress and renders existingAttempt card in quiz view', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'HTML Quest',
        'slug' => 'html-quest',
    ]);

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'HTML Basics',
        'slug' => 'html-basics',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Meaning of HTML',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 0,
    ]);

    QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Hyper-Text Markup Language',
        'is_correct' => true,
        'sort_order' => 0,
    ]);

    $optWrong = QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Hyper-Text Markup Line',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    // Student submits WRONG answer (score 0, is_passed false)
    $payload = [
        'time_spent_seconds' => 60,
        'answers' => [
            (string) $question->id => [$optWrong->id],
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'score' => 0,
                'max_score' => 10,
                'percentage' => 0.0,
                'is_passed' => false,
                'stars' => 0,
            ],
        ]);

    // 1. Progress page MUST list this quiz in completedQuizIds so it marks as finished and unlocks next step
    $progressResponse = $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]));

    $progressResponse->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds', [$quiz->id])
        );

    // 2. Visiting the quiz page again should render Quiz.vue with existingAttempt populated
    $quizViewResponse = $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]));

    $quizViewResponse->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->where('existingAttempt.percentage', 0)
            ->where('existingAttempt.score', 0)
            ->where('existingAttempt.is_passed', false)
        );
});
