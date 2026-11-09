<?php

use App\Enums\ConflictType;
use App\Enums\QuestionSelectionType;
use App\Enums\QuizType;
use App\Models\ColorConflictCard;
use App\Models\ConflictMapEvent;
use App\Models\ConflictMapZone;
use App\Models\EvaluationItem;
use App\Models\EvaluationItemOption;
use App\Models\MatchingChoice;
use App\Models\MatchingItem;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('multiple choice quiz with 3 items is scored, saved, and marked as completed on progress page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Multiple Choice Multi-Item Test',
        'slug' => 'multiple-choice-multi-item-test',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    // Create 3 questions with choices
    $q1 = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Question 1',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 1,
    ]);
    $o1Correct = QuizQuestionOption::create(['question_id' => $q1->id, 'option_text' => 'A', 'is_correct' => true, 'sort_order' => 0]);
    $o1Wrong = QuizQuestionOption::create(['question_id' => $q1->id, 'option_text' => 'B', 'is_correct' => false, 'sort_order' => 1]);

    $q2 = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Question 2',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 2,
    ]);
    $o2Correct = QuizQuestionOption::create(['question_id' => $q2->id, 'option_text' => 'A', 'is_correct' => true, 'sort_order' => 0]);
    $o2Wrong = QuizQuestionOption::create(['question_id' => $q2->id, 'option_text' => 'B', 'is_correct' => false, 'sort_order' => 1]);

    $q3 = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Question 3',
        'selection_type' => QuestionSelectionType::Multiple,
        'points' => 10,
        'sort_order' => 3,
    ]);
    $o3Correct1 = QuizQuestionOption::create(['question_id' => $q3->id, 'option_text' => 'A', 'is_correct' => true, 'sort_order' => 0]);
    $o3Correct2 = QuizQuestionOption::create(['question_id' => $q3->id, 'option_text' => 'B', 'is_correct' => true, 'sort_order' => 1]);
    $o3Wrong = QuizQuestionOption::create(['question_id' => $q3->id, 'option_text' => 'C', 'is_correct' => false, 'sort_order' => 2]);

    $submission = [
        'time_spent_seconds' => 60,
        'answers' => [
            (string) $q1->id => [$o1Correct->id],
            (string) $q2->id => [$o2Correct->id],
            (string) $q3->id => [$o3Correct1->id, $o3Correct2->id],
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $submission);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'score' => 30,
                'max_score' => 30,
                'percentage' => 100,
                'stars' => 3,
                'is_passed' => true,
            ],
        ]);

    $this->assertDatabaseCount('quiz_attempt_answers', 3);

    // Verify Progress page marks this quiz as completed in completedQuizIds
    $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->has('completedQuizIds', 1)
            ->where('completedQuizIds.0', $quiz->id)
        );
});

test('color the conflict quiz with 3 items is scored, saved, and marked as completed on progress page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Color Conflict Multi-Item Test',
        'slug' => 'color-conflict-multi-item-test',
        'type' => QuizType::ColorTheConflict,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    $c1 = ColorConflictCard::create([
        'quiz_id' => $quiz->id,
        'excerpt' => 'Leo fought the roaring blizzard on the cliff.',
        'conflict_type' => ConflictType::CharacterVsNature,
        'points' => 10,
        'sort_order' => 1,
    ]);

    $c2 = ColorConflictCard::create([
        'quiz_id' => $quiz->id,
        'excerpt' => 'Elena argued with the village chief at sunset.',
        'conflict_type' => ConflictType::CharacterVsCharacter,
        'points' => 10,
        'sort_order' => 2,
    ]);

    $c3 = ColorConflictCard::create([
        'quiz_id' => $quiz->id,
        'excerpt' => 'Marcus struggled with his fear of the dark cave.',
        'conflict_type' => ConflictType::CharacterVsSelf,
        'points' => 10,
        'sort_order' => 3,
    ]);

    $submission = [
        'time_spent_seconds' => 45,
        'answers' => [
            (string) $c1->id => ConflictType::CharacterVsNature->value,
            (string) $c2->id => ConflictType::CharacterVsCharacter->value,
            (string) $c3->id => ConflictType::CharacterVsSelf->value,
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $submission);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'score' => 30,
                'max_score' => 30,
                'percentage' => 100,
                'stars' => 3,
                'is_passed' => true,
            ],
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'is_passed' => true,
    ]);

    $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds.0', $quiz->id)
        );
});

test('conflict map quiz with 3 items is scored, saved, and marked as completed on progress page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Conflict Map Multi-Item Test',
        'slug' => 'conflict-map-multi-item-test',
        'type' => QuizType::TheConflictMap,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    ConflictMapZone::create([
        'quiz_id' => $quiz->id,
        'conflict_type' => ConflictType::CharacterVsNature,
        'label' => 'Nature Zone',
        'sort_order' => 1,
    ]);

    $ev1 = ConflictMapEvent::create([
        'quiz_id' => $quiz->id,
        'card_letter' => 'A',
        'event_text' => 'The hurricane knocked down the ancient tower.',
        'target_conflict_type' => ConflictType::CharacterVsNature,
        'points' => 10,
        'sort_order' => 1,
    ]);

    $ev2 = ConflictMapEvent::create([
        'quiz_id' => $quiz->id,
        'card_letter' => 'B',
        'event_text' => 'A flash flood swept away the wooden supplies.',
        'target_conflict_type' => ConflictType::CharacterVsNature,
        'points' => 10,
        'sort_order' => 2,
    ]);

    $ev3 = ConflictMapEvent::create([
        'quiz_id' => $quiz->id,
        'card_letter' => 'C',
        'event_text' => 'Lightning struck the high mast of the pirate ship.',
        'target_conflict_type' => ConflictType::CharacterVsNature,
        'points' => 10,
        'sort_order' => 3,
    ]);

    $submission = [
        'time_spent_seconds' => 50,
        'answers' => [
            (string) $ev1->id => ConflictType::CharacterVsNature->value,
            (string) $ev2->id => ConflictType::CharacterVsNature->value,
            (string) $ev3->id => ConflictType::CharacterVsNature->value,
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $submission);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'score' => 30,
                'max_score' => 30,
                'percentage' => 100,
                'stars' => 3,
                'is_passed' => true,
            ],
        ]);

    $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds.0', $quiz->id)
        );
});

test('matching type quiz with 3 items is scored, saved, and marked as completed on progress page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Matching Type Multi-Item Test',
        'slug' => 'matching-type-multi-item-test',
        'type' => QuizType::MatchingType,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    $chA = MatchingChoice::create(['quiz_id' => $quiz->id, 'choice_letter' => 'A', 'choice_text' => 'Exposition', 'sort_order' => 1]);
    $chB = MatchingChoice::create(['quiz_id' => $quiz->id, 'choice_letter' => 'B', 'choice_text' => 'Climax', 'sort_order' => 2]);
    $chC = MatchingChoice::create(['quiz_id' => $quiz->id, 'choice_letter' => 'C', 'choice_text' => 'Resolution', 'sort_order' => 3]);

    $item1 = MatchingItem::create([
        'quiz_id' => $quiz->id,
        'correct_choice_id' => $chA->id,
        'item_number' => 1,
        'description' => 'The story characters and setting are introduced.',
        'points' => 10,
        'sort_order' => 1,
    ]);

    $item2 = MatchingItem::create([
        'quiz_id' => $quiz->id,
        'correct_choice_id' => $chB->id,
        'item_number' => 2,
        'description' => 'The highest point of tension in the plot.',
        'points' => 10,
        'sort_order' => 2,
    ]);

    $item3 = MatchingItem::create([
        'quiz_id' => $quiz->id,
        'correct_choice_id' => $chC->id,
        'item_number' => 3,
        'description' => 'All conflicts are concluded and wrapped up.',
        'points' => 10,
        'sort_order' => 3,
    ]);

    $submission = [
        'time_spent_seconds' => 40,
        'answers' => [
            (string) $item1->id => 'A',
            (string) $item2->id => 'B',
            (string) $item3->id => 'C',
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $submission);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'score' => 30,
                'max_score' => 30,
                'percentage' => 100,
                'stars' => 3,
                'is_passed' => true,
            ],
        ]);

    $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds.0', $quiz->id)
        );
});

test('evaluation quiz with 3 items is scored, saved, and marked as completed on progress page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Evaluation Multi-Item Test',
        'slug' => 'evaluation-multi-item-test',
        'type' => QuizType::Evaluation,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    $evalItems = [];
    for ($i = 1; $i <= 3; $i++) {
        $item = EvaluationItem::create([
            'quiz_id' => $quiz->id,
            'section_title' => "Evaluation Section {$i}",
            'conflict_type' => 'character_vs_nature',
            'question_prompt' => "Evaluate scenario {$i}",
            'step1_prompt' => 'Pick stance:',
            'sentence_template' => 'I believe {blank1} demonstrated {blank2}.',
            'points' => 10,
            'sort_order' => $i,
        ]);

        $optCorrect = EvaluationItemOption::create([
            'item_id' => $item->id,
            'option_text' => 'Courageous Act',
            'stance_value' => 'support',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $evalItems[] = ['item' => $item, 'opt' => $optCorrect];
    }

    $submission = [
        'time_spent_seconds' => 55,
        'answers' => [
            (string) $evalItems[0]['item']->id => [
                'option_id' => $evalItems[0]['opt']->id,
                'blanks' => ['Tomas', 'bravery'],
            ],
            (string) $evalItems[1]['item']->id => [
                'option_id' => $evalItems[1]['opt']->id,
                'blanks' => ['Mira', 'resilience'],
            ],
            (string) $evalItems[2]['item']->id => [
                'option_id' => $evalItems[2]['opt']->id,
                'blanks' => ['Leo', 'fortitude'],
            ],
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $submission);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'pending_review',
            'is_pending_review' => true,
            'attempt' => [
                'status' => 'pending_review',
                'is_pending_review' => true,
                'score' => 0,
                'max_score' => 30,
                'percentage' => 0,
                'stars' => 0,
                'is_passed' => false,
            ],
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'status' => 'pending_review',
        'score' => 0,
    ]);

    // Verify Progress page still marks this quiz in completedQuizIds so student can advance
    $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds.0', $quiz->id)
            ->where('pendingQuizIds.0', $quiz->id)
        );

    // Teacher grades the evaluation quiz submission
    $attempt = QuizAttempt::where('quiz_id', $quiz->id)->first();
    $answers = $attempt->answers;

    $gradePayload = [
        'answers' => [
            ['id' => $answers[0]->id, 'points_earned' => 10, 'teacher_notes' => 'Great choice of stance and evidence'],
            ['id' => $answers[1]->id, 'points_earned' => 8, 'teacher_notes' => 'Solid reasoning'],
            ['id' => $answers[2]->id, 'points_earned' => 9, 'teacher_notes' => 'Very thoughtful'],
        ],
        'teacher_feedback' => 'Outstanding critical evaluation of all three scenarios!',
    ];

    $gradeResponse = $this->actingAs($teacher)
        ->postJson(route('teachers.grading.grade', ['attempt' => $attempt->id]), $gradePayload);

    $gradeResponse->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'status' => 'completed',
                'score' => 27,
                'max_score' => 30,
                'percentage' => 90,
                'stars' => 3,
                'is_passed' => true,
                'teacher_feedback' => 'Outstanding critical evaluation of all three scenarios!',
            ],
        ]);
});

test('reflection quiz with 3 items is scored as pending review, saved, and teacher can grade it', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Reflection Multi-Item Test',
        'slug' => 'reflection-multi-item-test',
        'type' => QuizType::Reflection,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'is_published' => true,
    ]);

    $p1 = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'What did the hero learn from facing the storm?',
        'min_words' => 5,
        'max_words' => 50,
        'points' => 10,
        'sort_order' => 1,
    ]);

    $p2 = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'How did teamwork help resolve the village disagreement?',
        'min_words' => 5,
        'max_words' => 50,
        'points' => 10,
        'sort_order' => 2,
    ]);

    $p3 = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'What would you have done differently in this chapter?',
        'min_words' => 5,
        'max_words' => 50,
        'points' => 10,
        'sort_order' => 3,
    ]);

    $submission = [
        'time_spent_seconds' => 75,
        'answers' => [
            (string) $p1->id => 'The hero learned that true strength comes from inner courage and persistence.',
            (string) $p2->id => 'Teamwork allowed all the villagers to listen to each other and compromise.',
            (string) $p3->id => 'I would have explored the eastern cave before making a final decision.',
        ],
    ];

    $response = $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), $submission);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'pending_review',
            'is_pending_review' => true,
            'attempt' => [
                'status' => 'pending_review',
                'is_pending_review' => true,
                'score' => 0,
                'max_score' => 30,
                'percentage' => 0,
                'stars' => 0,
                'is_passed' => false,
            ],
        ]);

    $this->actingAs($student)
        ->get(route('students.modules.show', ['module' => $module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('completedQuizIds.0', $quiz->id)
            ->where('pendingQuizIds.0', $quiz->id)
        );

    // Teacher grades reflection
    $attempt = QuizAttempt::where('quiz_id', $quiz->id)->first();
    $answers = $attempt->answers;

    $gradePayload = [
        'answers' => [
            ['id' => $answers[0]->id, 'points_earned' => 10, 'teacher_notes' => 'Heartfelt reflection'],
            ['id' => $answers[1]->id, 'points_earned' => 10, 'teacher_notes' => 'Great emphasis on teamwork'],
            ['id' => $answers[2]->id, 'points_earned' => 10, 'teacher_notes' => 'Creative perspective'],
        ],
        'teacher_feedback' => 'Wonderful reflections, keep up the deep thinking!',
    ];

    $gradeResponse = $this->actingAs($teacher)
        ->postJson(route('teachers.grading.grade', ['attempt' => $attempt->id]), $gradePayload);

    $gradeResponse->assertOk()
        ->assertJson([
            'success' => true,
            'attempt' => [
                'status' => 'completed',
                'score' => 30,
                'max_score' => 30,
                'percentage' => 100,
                'stars' => 3,
                'is_passed' => true,
            ],
        ]);
});
