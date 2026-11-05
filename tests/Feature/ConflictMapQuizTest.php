<?php

use App\Enums\ConflictType;
use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a teacher can view the conflict map quiz studio', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Conflict and Community Values',
        'slug' => 'conflict-and-community-values',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.the-conflict-map.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/ConflictMap')
            ->where('module.title', 'Conflict and Community Values')
            ->where('module.slug', 'conflict-and-community-values')
        );
});

test('a teacher can store a conflict map quiz with contexts, drop zones, and event cards', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'The Weavers of San Luis',
        'slug' => 'the-weavers-of-san-luis',
    ]);

    // Pre-create lecture so sort order is tested
    Lecture::factory()->for($module)->create(['sort_order' => 1]);

    $payload = [
        'title' => 'Activity 2: The Conflict Map (Drag-and-Drop)',
        'description' => 'Drag or select each event card into its matching conflict category zone below.',
        'xp_reward' => 180,
        'passing_percentage' => 70,
        'time_limit_seconds' => null,
        'is_published' => true,
        'contexts' => [
            [
                'scenario' => 'In the mountain weaving town of San Luis, artisans prepare for the royal blue textile contest amid rising floodwaters and strict town council regulations.',
                'hint' => 'Pay close attention to interpersonal rivalries vs institutional mandates vs weather threats.',
            ],
        ],
        'zones' => [
            [
                'conflict_type' => 'character_vs_character',
                'label' => 'CHARACTER VS CHARACTER',
                'subtitle' => 'Interpersonal conflict',
                'color_theme' => 'blue',
            ],
            [
                'conflict_type' => 'character_vs_society',
                'label' => 'CHARACTER VS SOCIETY',
                'subtitle' => 'Rules, laws, customs',
                'color_theme' => 'orange',
            ],
            [
                'conflict_type' => 'character_vs_nature',
                'label' => 'CHARACTER VS NATURE',
                'subtitle' => 'Weather and environmental forces',
                'color_theme' => 'green',
            ],
        ],
        'events' => [
            [
                'card_letter' => 'Card A',
                'event_text' => 'Marta takes the remaining batch of rare royal blue dye for her own design.',
                'target_conflict_type' => 'character_vs_character',
                'hint' => 'Marta action directly impacts fellow artisan Rosa.',
                'explanation' => 'Taking dye needed by another artisan sparks interpersonal rivalry.',
                'points' => 10,
            ],
            [
                'card_letter' => 'Card B',
                'event_text' => 'The town council creates a policy awarding only ONE grand prize, promoting competition.',
                'target_conflict_type' => 'character_vs_society',
                'hint' => 'This rule is mandated by the governing council.',
                'explanation' => 'Town council policies represent systemic societal pressure.',
                'points' => 10,
            ],
            [
                'card_letter' => 'Card C',
                'event_text' => 'A violent tropical storm hits San Luis, threatening to flood drying racks.',
                'target_conflict_type' => 'character_vs_nature',
                'hint' => 'A tropical storm is an environmental threat.',
                'explanation' => 'The storm is an uncontrollable force of nature.',
                'points' => 10,
            ],
            [
                'card_letter' => 'Card D',
                'event_text' => 'Rosa and Marta argue over who should use the royal blue dye first.',
                'target_conflict_type' => 'character_vs_character',
                'hint' => 'Two characters are directly clashing.',
                'explanation' => 'Direct interpersonal argument between two individuals.',
                'points' => 10,
            ],
            [
                'card_letter' => 'Card E',
                'event_text' => 'Rosa and Marta wade through knee-deep water to save looms from floodwaters.',
                'target_conflict_type' => 'character_vs_nature',
                'hint' => 'They are battling rising floodwaters.',
                'explanation' => 'The rising floodwaters are natural hazards confronting the characters.',
                'points' => 10,
            ],
            [
                'card_letter' => 'Card F',
                'event_text' => 'Officials demand modern, factory-dyed aesthetics over slow traditional methods.',
                'target_conflict_type' => 'character_vs_society',
                'hint' => 'Government officials dictating aesthetic customs.',
                'explanation' => 'Institutional demands challenge traditional community cultural practices.',
                'points' => 10,
            ],
        ],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.the-conflict-map.store', $module->slug), $payload);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'type' => QuizType::TheConflictMap->value,
        'title' => 'Activity 2: The Conflict Map (Drag-and-Drop)',
        'sort_order' => 2, // 1 was lecture, so quiz gets 2!
        'xp_reward' => 180,
    ]);

    /** @var Quiz $quiz */
    $quiz = Quiz::where('module_id', $module->id)->where('type', QuizType::TheConflictMap)->firstOrFail();

    // Verify context
    expect($quiz->contexts)->toHaveCount(1);
    expect($quiz->contexts[0]->scenario)->toContain('San Luis');

    // Verify zones
    expect($quiz->mapZones)->toHaveCount(3);
    expect($quiz->mapZones[0]->conflict_type)->toBe(ConflictType::CharacterVsCharacter);
    expect($quiz->mapZones[1]->conflict_type)->toBe(ConflictType::CharacterVsSociety);
    expect($quiz->mapZones[2]->conflict_type)->toBe(ConflictType::CharacterVsNature);

    // Verify events
    expect($quiz->mapEvents)->toHaveCount(6);
    expect($quiz->mapEvents[0]->card_letter)->toBe('Card A');
    expect($quiz->mapEvents[0]->target_conflict_type)->toBe(ConflictType::CharacterVsCharacter);
    expect($quiz->mapEvents[0]->zone_id)->toBe($quiz->mapZones[0]->id);

    expect($quiz->mapEvents[1]->card_letter)->toBe('Card B');
    expect($quiz->mapEvents[1]->target_conflict_type)->toBe(ConflictType::CharacterVsSociety);
    expect($quiz->mapEvents[1]->zone_id)->toBe($quiz->mapZones[1]->id);

    expect($quiz->mapEvents[2]->card_letter)->toBe('Card C');
    expect($quiz->mapEvents[2]->target_conflict_type)->toBe(ConflictType::CharacterVsNature);
    expect($quiz->mapEvents[2]->zone_id)->toBe($quiz->mapZones[2]->id);
});
