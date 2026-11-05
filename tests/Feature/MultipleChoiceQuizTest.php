<?php

use App\Enums\QuestionSelectionType;
use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a teacher can view the multiple choice quiz creation studio', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Conflict and Community Values',
        'slug' => 'conflict-and-community-values',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.multiple-choice.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/MultipleChoice')
            ->where('module.title', 'Conflict and Community Values')
            ->where('module.slug', 'conflict-and-community-values')
        );
});

test('a teacher can store a multiple choice quiz with questions and choices', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Philippine Folklore Legends',
        'slug' => 'philippine-folklore-legends',
    ]);

    $payload = [
        'title' => 'Chapter 1 Checkpoint: Conflict & Bayanihan',
        'description' => 'Test your understanding of the villagers resolving the river crossing crisis.',
        'reading_passage' => 'When the bamboo bridge washed away, the elders called all families together to lift the houses on their shoulders.',
        'xp_reward' => 200,
        'passing_percentage' => 75,
        'time_limit_seconds' => 300,
        'is_published' => true,
        'questions' => [
            [
                'question_text' => 'What natural event caused the villagers to assemble?',
                'selection_type' => 'single',
                'passage_context' => 'When the bamboo bridge washed away...',
                'hint' => 'Look at the first sentence of the story passage.',
                'explanation' => 'The passage states that the bamboo bridge washed away during the flash flood.',
                'points' => 10,
                'options' => [
                    ['option_text' => 'The bamboo bridge washed away', 'is_correct' => true, 'feedback' => 'Correct!'],
                    ['option_text' => 'A wildfire reached the village', 'is_correct' => false, 'feedback' => null],
                    ['option_text' => 'A volcano erupted nearby', 'is_correct' => false, 'feedback' => null],
                    ['option_text' => 'A harvest celebration began', 'is_correct' => false, 'feedback' => null],
                ],
            ],
            [
                'question_text' => 'Which values were demonstrated by the community members? (Select all that apply)',
                'selection_type' => 'multiple',
                'passage_context' => null,
                'hint' => 'Bayanihan represents collective action and mutual support.',
                'explanation' => 'Both solidarity and mutual assistance reflect true Bayanihan.',
                'points' => 20,
                'options' => [
                    ['option_text' => 'Bayanihan (mutual unity)', 'is_correct' => true, 'feedback' => null],
                    ['option_text' => 'Solidarity in times of crisis', 'is_correct' => true, 'feedback' => null],
                    ['option_text' => 'Individual competition', 'is_correct' => false, 'feedback' => null],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.multiple-choice.store', $module->slug), $payload);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'type' => QuizType::MultipleChoice->value,
        'title' => 'Chapter 1 Checkpoint: Conflict & Bayanihan',
        'slug' => 'chapter-1-checkpoint-conflict-bayanihan',
        'xp_reward' => 200,
        'passing_percentage' => 75,
        'sort_order' => 1,
    ]);

    $quiz = Quiz::where('slug', 'chapter-1-checkpoint-conflict-bayanihan')->firstOrFail();

    $this->assertDatabaseHas('quiz_contexts', [
        'quiz_id' => $quiz->id,
        'scenario' => 'When the bamboo bridge washed away, the elders called all families together to lift the houses on their shoulders.',
    ]);

    expect($quiz->context)->not->toBeNull()
        ->and($quiz->context->scenario)->toContain('bamboo bridge')
        ->and($quiz->context->quiz->id)->toBe($quiz->id)
        ->and($quiz->questions)->toHaveCount(2)
        ->and($quiz->questions[0]->selection_type)->toBe(QuestionSelectionType::Single)
        ->and($quiz->questions[0]->options)->toHaveCount(4)
        ->and($quiz->questions[0]->options()->where('is_correct', true)->count())->toBe(1)
        ->and($quiz->questions[1]->selection_type)->toBe(QuestionSelectionType::Multiple)
        ->and($quiz->questions[1]->options)->toHaveCount(3)
        ->and($quiz->questions[1]->options()->where('is_correct', true)->count())->toBe(2);
});

test('validation requires at least one question and at least two options per question', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();

    $invalidPayload = [
        'title' => 'Incomplete Quiz',
        'xp_reward' => 100,
        'passing_percentage' => 70,
        'questions' => [],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.multiple-choice.store', $module->slug), $invalidPayload);

    $response->assertSessionHasErrors(['questions']);
});

test('deleting a module cascades and deletes its quizzes, questions, and options', function () {
    $module = Module::factory()->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'type' => QuizType::MultipleChoice,
        'title' => 'Cascading Quiz',
        'slug' => 'cascading-quiz',
        'xp_reward' => 100,
        'passing_percentage' => 70,
        'sort_order' => 1,
    ]);

    $question = $quiz->questions()->create([
        'question_text' => 'Cascade question prompt',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 1,
    ]);

    $option = $question->options()->create([
        'option_text' => 'Cascade option A',
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    $quizContext = $quiz->context()->create([
        'scenario' => 'A mysterious island appears out of the fog.',
        'hint' => 'Follow the light from the ancient tower.',
    ]);

    $module->delete();

    $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    $this->assertDatabaseMissing('quiz_contexts', ['id' => $quizContext->id]);
    $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
    $this->assertDatabaseMissing('quiz_question_options', ['id' => $option->id]);
});

test('quiz context stores quiz_id, scenario text, and hint in dedicated table and links to quiz', function () {
    $module = Module::factory()->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'type' => QuizType::MultipleChoice,
        'title' => 'Context Foundation Quiz',
        'slug' => 'context-foundation-quiz',
        'xp_reward' => 150,
        'passing_percentage' => 70,
        'sort_order' => 1,
    ]);

    $context = QuizContext::create([
        'quiz_id' => $quiz->id,
        'scenario' => 'In a quiet coastal town, fishermen rely on community teamwork to launch the heavy wooden bangka boats.',
        'hint' => 'Notice the cooperative spirit displayed by all neighbors.',
    ]);

    $this->assertDatabaseHas('quiz_contexts', [
        'id' => $context->id,
        'quiz_id' => $quiz->id,
        'scenario' => 'In a quiet coastal town, fishermen rely on community teamwork to launch the heavy wooden bangka boats.',
        'hint' => 'Notice the cooperative spirit displayed by all neighbors.',
    ]);

    expect($quiz->context)->not->toBeNull()
        ->and($quiz->context->id)->toBe($context->id)
        ->and($quiz->context->scenario)->toContain('fishermen rely on community teamwork')
        ->and($quiz->context->hint)->toBe('Notice the cooperative spirit displayed by all neighbors.')
        ->and($context->quiz->id)->toBe($quiz->id)
        ->and($context->quiz->title)->toBe('Context Foundation Quiz');
});

test('when a lecture is added first on step 1, a newly added quiz is assigned step 2', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Island Legends',
        'slug' => 'island-legends',
    ]);

    // Step 1: Lecture added first
    $lecture = Lecture::factory()->for($module)->create([
        'title' => 'Lecture 1: The Mountain Guardian',
        'slug' => 'lecture-1-the-mountain-guardian',
        'sort_order' => 1,
    ]);

    // Studio create screen shows next milestone step is 2
    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.multiple-choice.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/MultipleChoice')
            ->where('module.next_sort_order', 2)
        );

    // Step 2: Teacher adds a Quiz
    $payload = [
        'title' => 'Quiz 2: Guardian Checkpoint',
        'description' => 'Test comprehension of the mountain guardian story.',
        'xp_reward' => 150,
        'passing_percentage' => 70,
        'questions' => [
            [
                'question_text' => 'Who guarded the mountain?',
                'selection_type' => 'single',
                'points' => 10,
                'options' => [
                    ['option_text' => 'The golden eagle', 'is_correct' => true, 'feedback' => null],
                    ['option_text' => 'The sea turtle', 'is_correct' => false, 'feedback' => null],
                ],
            ],
        ],
    ];

    $this->actingAs($teacher)
        ->post(route('teachers.quizzes.multiple-choice.store', $module->slug), $payload)
        ->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'title' => 'Quiz 2: Guardian Checkpoint',
        'sort_order' => 2,
    ]);

    // Viewing the journey map contains both lecture and quiz
    $this->actingAs($teacher)
        ->get(route('teachers.modules.show', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/view/Module')
            ->has('lectures', 1)
            ->has('quizzes', 1)
            ->where('lectures.0.sort_order', 1)
            ->where('quizzes.0.sort_order', 2)
        );
});

test('when a quiz is added first on step 1, a newly added lecture is assigned step 2', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Deep Sea Reading',
        'slug' => 'deep-sea-reading',
    ]);

    // Step 1: Quiz added first
    $quiz = Quiz::create([
        'module_id' => $module->id,
        'type' => QuizType::MultipleChoice,
        'title' => 'Diagnostic Assessment',
        'slug' => 'diagnostic-assessment',
        'xp_reward' => 100,
        'passing_percentage' => 70,
        'sort_order' => 1,
    ]);

    // Teacher lecture create studio shows next milestone step is 2
    $this->actingAs($teacher)
        ->get(route('teachers.lectures.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/Lecture')
            ->where('module.next_sort_order', 2)
        );

    // Teacher creates lecture
    $this->actingAs($teacher)
        ->post(route('teachers.lectures.store', $module->slug), [
            'title' => 'Lecture 2: Coral Secrets',
            'description' => 'Dive into underwater storytelling.',
            'xp_reward' => 150,
        ])
        ->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('lectures', [
        'module_id' => $module->id,
        'title' => 'Lecture 2: Coral Secrets',
        'sort_order' => 2,
    ]);
});

test('a teacher can view the multiple choice quiz detail inspector', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'type' => QuizType::MultipleChoice,
        'title' => 'Inspector Quiz Test',
        'slug' => 'inspector-quiz-test',
        'xp_reward' => 150,
        'passing_percentage' => 75,
        'sort_order' => 2,
    ]);

    $question = $quiz->questions()->create([
        'question_text' => 'What is the moral of the fable?',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 1,
    ]);

    $question->options()->create([
        'option_text' => 'Honesty and collaboration build strong communities.',
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.multiple-choice.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/view/quizes/MultipleChoice')
            ->where('quiz.title', 'Inspector Quiz Test')
            ->where('quiz.sort_order', 2)
            ->has('quiz.questions', 1)
        );
});

test('a teacher can create a multiple choice quiz with multiple context scenarios', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();

    $payload = [
        'title' => 'Multi-Context Reading Quest',
        'description' => 'Quiz referencing multiple story situations.',
        'xp_reward' => 250,
        'passing_percentage' => 80,
        'contexts' => [
            [
                'scenario' => 'Scenario 1: The village elders gathered by the riverbank to discuss the upcoming monsoon.',
                'hint' => 'Note what the elders decided about reinforcing the dikes.',
            ],
            [
                'scenario' => 'Scenario 2: The young volunteers gathered bamboo poles and vines to reinforce the community bridge.',
                'hint' => 'Look at the role of the youth group.',
            ],
        ],
        'questions' => [
            [
                'question_text' => 'What was the primary concern of the elders in Scenario 1?',
                'selection_type' => 'single',
                'points' => 15,
                'options' => [
                    ['option_text' => 'Reinforcing river dikes before monsoon', 'is_correct' => true, 'feedback' => 'Correct!'],
                    ['option_text' => 'Harvesting crops early', 'is_correct' => false, 'feedback' => null],
                ],
            ],
        ],
    ];

    $this->actingAs($teacher)
        ->post(route('teachers.quizzes.multiple-choice.store', $module->slug), $payload)
        ->assertRedirect(route('teachers.modules.show', $module->slug));

    $quiz = Quiz::where('title', 'Multi-Context Reading Quest')->firstOrFail();

    expect($quiz->contexts)->toHaveCount(2)
        ->and($quiz->contexts[0]->scenario)->toContain('Scenario 1: The village elders')
        ->and($quiz->contexts[0]->hint)->toContain('reinforcing the dikes')
        ->and($quiz->contexts[1]->scenario)->toContain('Scenario 2: The young volunteers')
        ->and($quiz->contexts[1]->hint)->toContain('youth group');

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.multiple-choice.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/view/quizes/MultipleChoice')
            ->has('quiz.contexts', 2)
            ->where('quiz.contexts.0.scenario', 'Scenario 1: The village elders gathered by the riverbank to discuss the upcoming monsoon.')
            ->where('quiz.contexts.1.scenario', 'Scenario 2: The young volunteers gathered bamboo poles and vines to reinforce the community bridge.')
        );
});
