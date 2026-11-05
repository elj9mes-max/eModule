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

test('a teacher can view the color the conflict quiz studio', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Conflict and Community Values',
        'slug' => 'conflict-and-community-values',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.color-the-conflict.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/ColortheConflict')
            ->where('module.title', 'Conflict and Community Values')
            ->where('module.slug', 'conflict-and-community-values')
        );
});

test('a non-owner cannot access color the conflict creation studio', function () {
    $teacher1 = User::factory()->teacher()->create();
    $teacher2 = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher1, 'teacher')->create();

    $this->actingAs($teacher2)
        ->get(route('teachers.quizzes.color-the-conflict.create', $module->slug))
        ->assertRedirect(route('teachers.dashboard'));
});

test('a teacher can store a color the conflict quiz with 6 sequential cards and multiple contexts', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Philippine Folklore Legends',
        'slug' => 'philippine-folklore-legends',
    ]);

    // Pre-create lecture so sort order is tested
    Lecture::factory()->for($module)->create(['sort_order' => 1]);

    $payload = [
        'title' => 'Color the Conflict: Tales of Mount Kanlaon',
        'description' => 'Classify the forces driving each excerpt from the mountain legend.',
        'xp_reward' => 200,
        'passing_percentage' => 70,
        'time_limit_seconds' => null,
        'is_published' => true,
        'contexts' => [
            [
                'scenario' => 'Deep within the dense bamboo rainforest of Negros, the villagers face trials of loyalty and weather.',
                'hint' => 'Focus on what threatens their peaceful village.',
            ],
            [
                'scenario' => 'The village chief debates with the council regarding the ancient taboos.',
                'hint' => 'Look for conflicting social rules vs personal duties.',
            ],
        ],
        'cards' => [
            [
                'excerpt' => 'Datu Tagob and warrior Lapu argued fiercely over who should lead the hunting party through the sacred pass.',
                'conflict_type' => 'character_vs_character',
                'hint' => 'Two characters are locking horns directly.',
                'explanation' => 'Correct! This is an interpersonal clash between two individuals.',
                'feedback_incorrect' => 'Notice the two named people arguing.',
                'points' => 10,
            ],
            [
                'excerpt' => 'The elders decreed that no youth may cross the northern ridge after dark, under pain of banishment.',
                'conflict_type' => 'character_vs_society',
                'hint' => 'Consider who made the law and who must obey.',
                'explanation' => 'Correct! The tradition and tribal laws of the community restrict the character.',
                'feedback_incorrect' => 'This conflict stems from rules set by society.',
                'points' => 10,
            ],
            [
                'excerpt' => 'A sudden torrential monsoon swelled the river, ripping century-old trees from their roots.',
                'conflict_type' => 'character_vs_nature',
                'hint' => 'Look at the force causing devastation.',
                'explanation' => 'Correct! The wild monsoon storm is a natural environmental force.',
                'feedback_incorrect' => 'The storm is an element of nature.',
                'points' => 10,
            ],
            [
                'excerpt' => 'Maya stared at the stolen amulet, trembling with guilt and fearing she had betrayed her family trust.',
                'conflict_type' => 'character_vs_self',
                'hint' => 'The battle is happening inside Maya conscience.',
                'explanation' => 'Correct! Maya is wrestling with her internal moral dilemma.',
                'feedback_incorrect' => 'This is an internal struggle within Maya herself.',
                'points' => 10,
            ],
            [
                'excerpt' => 'The village tax collector demanded double grains despite the poor harvest, but the farmers resisted in protest.',
                'conflict_type' => 'character_vs_society',
                'hint' => 'The protest is against an institutional system.',
                'explanation' => 'Correct! The conflict pits the citizens against the institutional ruling system.',
                'feedback_incorrect' => 'The farmers are opposing laws and institutional demands.',
                'points' => 10,
            ],
            [
                'excerpt' => 'Kiko shielded his eyes as the volcanic ash cloud billowed down the slope, choking his breath.',
                'conflict_type' => 'character_vs_nature',
                'hint' => 'A volcanic eruption is an act of the earth.',
                'explanation' => 'Correct! Volcanic ash and mountain hazards are environmental forces.',
                'feedback_incorrect' => 'The volcano is a powerful force of nature.',
                'points' => 10,
            ],
        ],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.color-the-conflict.store', $module->slug), $payload);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'type' => QuizType::ColorTheConflict->value,
        'title' => 'Color the Conflict: Tales of Mount Kanlaon',
        'slug' => 'color-the-conflict-tales-of-mount-kanlaon',
        'xp_reward' => 200,
        'passing_percentage' => 70,
        'sort_order' => 2, // 1 was lecture, so quiz gets 2!
        'is_published' => true,
    ]);

    /** @var Quiz $quiz */
    $quiz = Quiz::where('slug', 'color-the-conflict-tales-of-mount-kanlaon')->firstOrFail();

    // Verify contexts in quiz_contexts table
    expect($quiz->contexts)->toHaveCount(2);
    expect($quiz->contexts[0]->scenario)->toContain('Deep within the dense bamboo rainforest');
    expect($quiz->contexts[1]->scenario)->toContain('The village chief debates with the council');

    // Verify 6 conflict cards in color_conflict_cards table
    expect($quiz->conflictCards)->toHaveCount(6);
    expect($quiz->conflictCards[0]->conflict_type)->toBe(ConflictType::CharacterVsCharacter);
    expect($quiz->conflictCards[0]->sort_order)->toBe(1);
    expect($quiz->conflictCards[1]->conflict_type)->toBe(ConflictType::CharacterVsSociety);
    expect($quiz->conflictCards[1]->sort_order)->toBe(2);
    expect($quiz->conflictCards[2]->conflict_type)->toBe(ConflictType::CharacterVsNature);
    expect($quiz->conflictCards[2]->sort_order)->toBe(3);
    expect($quiz->conflictCards[3]->conflict_type)->toBe(ConflictType::CharacterVsSelf);
    expect($quiz->conflictCards[3]->sort_order)->toBe(4);
});
