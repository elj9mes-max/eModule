<?php

use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a teacher can view the reflection quiz studio', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Woven in Bayanihan',
        'slug' => 'woven-in-bayanihan',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.reflection.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/Reflection')
            ->where('module.title', 'Woven in Bayanihan')
            ->where('module.slug', 'woven-in-bayanihan')
        );
});

test('a teacher can store a reflection quiz with scenario context and custom word limits', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Woven in Bayanihan',
        'slug' => 'woven-in-bayanihan',
    ]);

    // Pre-create lecture so sort order is tested
    Lecture::factory()->for($module)->create(['sort_order' => 1]);

    $payload = [
        'title' => 'Personal Response: Lessons from the Loom',
        'description' => 'Take some time to reflect on what happened in the story.',
        'xp_reward' => 250,
        'passing_percentage' => 70,
        'time_limit_seconds' => null,
        'is_published' => true,
        'contexts' => [
            [
                'scenario' => 'Aling Marta and Aling Rosa chose cooperation over competition during the storm.',
                'hint' => 'Think of a time when helping others made a difficult situation easier.',
            ],
        ],
        'prompts' => [
            [
                'prompt_text' => 'If you were Aling Marta, would you have shared the dye right away, or kept it to win the competition? Explain why.',
                'max_words' => 150,
                'min_words' => 25,
                'guiding_hint' => 'Consider how working together helped save the drying racks from the storm.',
                'starter_sentence' => 'If I were in Aling Marta’s position, I would...',
                'points' => 50,
            ],
            [
                'prompt_text' => 'What does "Bayanihan" mean to you in your own life at school or at home?',
                'max_words' => 100,
                'min_words' => null,
                'guiding_hint' => 'Give a real-life example of helping a classmate or family member.',
                'starter_sentence' => 'To me, Bayanihan means...',
                'points' => 50,
            ],
        ],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.reflection.store', $module->slug), $payload);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));
    $response->assertSessionHas('status', 'Reflection activity created successfully!');

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'type' => QuizType::Reflection->value,
        'title' => 'Personal Response: Lessons from the Loom',
        'sort_order' => 2,
        'xp_reward' => 250,
        'passing_percentage' => 70,
        'is_published' => 1,
    ]);

    /** @var Quiz $quiz */
    $quiz = Quiz::where('module_id', $module->id)->where('type', QuizType::Reflection)->first();
    expect($quiz)->not->toBeNull();

    expect($quiz->contexts()->count())->toBe(1);
    expect($quiz->contexts()->first()->scenario)->toContain('Aling Marta and Aling Rosa');

    expect($quiz->reflectionPrompts()->count())->toBe(2);

    $firstPrompt = $quiz->reflectionPrompts()->where('sort_order', 1)->first();
    expect($firstPrompt->prompt_text)->toContain('If you were Aling Marta')
        ->and($firstPrompt->max_words)->toBe(150)
        ->and($firstPrompt->min_words)->toBe(25)
        ->and($firstPrompt->starter_sentence)->toContain('If I were in Aling Marta’s position')
        ->and($firstPrompt->points)->toBe(50);

    $secondPrompt = $quiz->reflectionPrompts()->where('sort_order', 2)->first();
    expect($secondPrompt->prompt_text)->toContain('Bayanihan')
        ->and($secondPrompt->max_words)->toBe(100)
        ->and($secondPrompt->min_words)->toBeNull()
        ->and($secondPrompt->starter_sentence)->toContain('To me, Bayanihan means')
        ->and($secondPrompt->points)->toBe(50);
});

test('a reflection quiz requires title and at least one prompt with max_words', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Woven in Bayanihan',
    ]);

    $this->actingAs($teacher)
        ->post(route('teachers.quizzes.reflection.store', $module->slug), [
            'title' => '',
            'prompts' => [],
        ])
        ->assertSessionHasErrors(['title', 'prompts']);
});

test('another teacher cannot create a reflection quiz in a module they do not own', function () {
    $owner = User::factory()->teacher()->create();
    $intruder = User::factory()->teacher()->create();

    $module = Module::factory()->for($owner, 'teacher')->create();

    $this->actingAs($intruder)
        ->get(route('teachers.quizzes.reflection.create', $module->slug))
        ->assertRedirect(route('teachers.dashboard'));

    $this->actingAs($intruder)
        ->post(route('teachers.quizzes.reflection.store', $module->slug), [
            'title' => 'Unauthorized Reflection',
            'xp_reward' => 200,
            'passing_percentage' => 70,
            'prompts' => [
                [
                    'prompt_text' => 'Test prompt',
                    'max_words' => 150,
                    'points' => 50,
                ],
            ],
        ])
        ->assertRedirect(route('teachers.dashboard'));

    expect(Quiz::where('title', 'Unauthorized Reflection')->exists())->toBeFalse();
});
