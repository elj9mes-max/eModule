<?php

use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a teacher can view the evaluation quiz studio', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Woven in Bayanihan',
        'slug' => 'woven-in-bayanihan',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.evaluation.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/Evaluation')
            ->where('module.title', 'Woven in Bayanihan')
            ->where('module.slug', 'woven-in-bayanihan')
        );
});

test('a teacher can store an evaluation quiz with word helpers and stance blanks', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Woven in Bayanihan',
        'slug' => 'woven-in-bayanihan',
    ]);

    // Pre-create lecture so sort order is tested
    Lecture::factory()->for($module)->create(['sort_order' => 1]);

    $payload = [
        'title' => 'Story Map Analysis: Critical Evaluation',
        'description' => 'Directions: Read each question carefully. Use the Sentence Starters, Choice Buttons, and Word Helpers to complete your answers in 1–2 simple sentences.',
        'xp_reward' => 250,
        'passing_percentage' => 70,
        'time_limit_seconds' => null,
        'is_published' => true,
        'contexts' => [
            [
                'scenario' => 'In "Woven in Bayanihan", Aling Rosa and Aling Marta face rivalry and floodwaters.',
                'hint' => 'Focus on moral choices, cooperation, and community values.',
            ],
        ],
        'word_helpers' => [
            'selfish',
            'bayanihan (cooperation)',
            'unfair',
            'kindness',
            'unity',
            'shared',
            'help',
            'compete',
        ],
        'items' => [
            [
                'section_title' => '1. EVALUATING CHARACTER VS. CHARACTER',
                'conflict_type' => 'character_vs_character',
                'question_prompt' => "Was Aling Marta right to take all the dye to meet the council's demands? Why or why not?",
                'step1_prompt' => 'Step 1: Pick one option:',
                'sentence_template' => 'Aling Marta was [stance] (right / wrong) because she should have [blank].',
                'hint' => 'Think about whether sharing or taking everything is fair!',
                'points' => 25,
                'options' => [
                    ['option_text' => 'Yes, she was right.', 'stance_value' => 'right', 'is_correct' => false],
                    ['option_text' => 'No, she was wrong.', 'stance_value' => 'wrong', 'is_correct' => true],
                ],
            ],
            [
                'section_title' => '2. EVALUATING CHARACTER VS. NATURE',
                'conflict_type' => 'character_vs_nature',
                'question_prompt' => 'How did facing the storm together change the way Aling Rosa and Marta treated each other? Was helping each other during a crisis a good choice?',
                'step1_prompt' => 'Step 1: Pick how they changed:',
                'sentence_template' => 'Facing the storm made them realize that helping each other was a [stance] choice because [blank].',
                'hint' => 'Think about what happens when people work together during a bad storm!',
                'points' => 25,
                'options' => [
                    ['option_text' => 'They became enemies.', 'stance_value' => 'bad', 'is_correct' => false],
                    ['option_text' => 'They became friends again.', 'stance_value' => 'good', 'is_correct' => true],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.evaluation.store', $module->slug), $payload);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'type' => QuizType::Evaluation->value,
        'title' => 'Story Map Analysis: Critical Evaluation',
        'sort_order' => 2, // 1 was lecture, so quiz gets 2!
        'xp_reward' => 250,
    ]);

    /** @var Quiz $quiz */
    $quiz = Quiz::where('module_id', $module->id)->where('type', QuizType::Evaluation)->firstOrFail();

    // Verify context
    expect($quiz->contexts)->toHaveCount(1);
    expect($quiz->contexts[0]->scenario)->toContain('Woven in Bayanihan');

    // Verify word helpers
    expect($quiz->evaluationWordHelpers)->toHaveCount(8);
    expect($quiz->evaluationWordHelpers[0]->word)->toBe('selfish');
    expect($quiz->evaluationWordHelpers[1]->word)->toBe('bayanihan (cooperation)');

    // Verify items
    expect($quiz->evaluationItems)->toHaveCount(2);

    $item1 = $quiz->evaluationItems[0];
    expect($item1->section_title)->toBe('1. EVALUATING CHARACTER VS. CHARACTER');
    expect($item1->sentence_template)->toContain('[stance]');
    expect($item1->sentence_template)->toContain('[blank]');
    expect($item1->options)->toHaveCount(2);
    expect($item1->options[1]->option_text)->toBe('No, she was wrong.');
    expect($item1->options[1]->stance_value)->toBe('wrong');
    expect($item1->options[1]->is_correct)->toBeTrue();

    $item2 = $quiz->evaluationItems[1];
    expect($item2->section_title)->toBe('2. EVALUATING CHARACTER VS. NATURE');
    expect($item2->options)->toHaveCount(2);
    expect($item2->options[1]->option_text)->toBe('They became friends again.');
    expect($item2->options[1]->stance_value)->toBe('good');
});
