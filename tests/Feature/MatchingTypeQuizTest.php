<?php

use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a teacher can view the matching type quiz studio', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Sabutan Weavers of Aurora',
        'slug' => 'sabutan-weavers-of-aurora',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.matching-type.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/MatchingType')
            ->where('module.title', 'Sabutan Weavers of Aurora')
            ->where('module.slug', 'sabutan-weavers-of-aurora')
        );
});

test('a teacher can store a matching type quiz with contexts, choices, and description items', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Sabutan Weavers of Aurora',
        'slug' => 'sabutan-weavers-of-aurora',
    ]);

    // Pre-create lecture so sort order is tested
    Lecture::factory()->for($module)->create(['sort_order' => 1]);

    $payload = [
        'title' => 'Matching Type: Characters, Settings & Conflicts',
        'description' => 'Directions: Read the descriptions in Column A (what happened in the story) and match them with the correct character, place, or conflict type in Column B.',
        'xp_reward' => 220,
        'passing_percentage' => 70,
        'time_limit_seconds' => null,
        'is_published' => true,
        'contexts' => [
            [
                'scenario' => 'In the coastal weaving town of San Luis, Aurora, Aling Rosa and Aling Marta struggle with the town council policy and the sudden tropical storm that threatens their sabutan leaves.',
                'hint' => 'Review the story characters and where the weaving take place.',
            ],
        ],
        'choices' => [
            ['choice_letter' => 'A', 'choice_text' => 'Character vs. Character'],
            ['choice_letter' => 'B', 'choice_text' => 'Character vs. Nature'],
            ['choice_letter' => 'C', 'choice_text' => 'Character vs. Society'],
            ['choice_letter' => 'D', 'choice_text' => 'San Luis'],
            ['choice_letter' => 'E', 'choice_text' => 'Aling Marta'],
        ],
        'items' => [
            [
                'item_number' => 1,
                'description' => 'The coastal town in Aurora where Aling Rosa and Aling Marta live.',
                'correct_choice_letter' => 'D',
                'hint' => 'Look for the geographical location in Aurora.',
                'explanation' => 'San Luis is the coastal town in Aurora where the weavers reside.',
                'points' => 10,
            ],
            [
                'item_number' => 2,
                'description' => 'The weaver who took all the royal blue dye for her own tapestry.',
                'correct_choice_letter' => 'E',
                'hint' => 'Identify the individual weaver who took the dye.',
                'explanation' => 'Aling Marta took the remaining batch of rare royal blue dye.',
                'points' => 10,
            ],
            [
                'item_number' => 3,
                'description' => 'The sudden tropical storm that flooded the drying racks and threatened to ruin the sabutan leaves.',
                'correct_choice_letter' => 'B',
                'hint' => 'Tropical storms and weather hazards represent environmental forces.',
                'explanation' => 'The storm is an uncontrollable force of nature.',
                'points' => 10,
            ],
            [
                'item_number' => 4,
                'description' => 'The argument between Aling Rosa and Aling Marta over the shared blue dye.',
                'correct_choice_letter' => 'A',
                'hint' => 'Two characters are in direct interpersonal conflict.',
                'explanation' => 'A direct interpersonal clash between two individuals.',
                'points' => 10,
            ],
            [
                'item_number' => 5,
                'description' => 'The struggle against the town council policy that forced elder weavers to compete instead of sharing.',
                'correct_choice_letter' => 'C',
                'hint' => 'The dispute is against rules and institutions set by society.',
                'explanation' => 'The weavers are confronting the rules and policies of the town council.',
                'points' => 10,
            ],
        ],
    ];

    $response = $this->actingAs($teacher)
        ->post(route('teachers.quizzes.matching-type.store', $module->slug), $payload);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('quizzes', [
        'module_id' => $module->id,
        'type' => QuizType::MatchingType->value,
        'title' => 'Matching Type: Characters, Settings & Conflicts',
        'sort_order' => 2, // 1 was lecture, so quiz gets 2!
        'xp_reward' => 220,
    ]);

    /** @var Quiz $quiz */
    $quiz = Quiz::where('module_id', $module->id)->where('type', QuizType::MatchingType)->firstOrFail();

    // Verify context
    expect($quiz->contexts)->toHaveCount(1);
    expect($quiz->contexts[0]->scenario)->toContain('San Luis, Aurora');

    // Verify Column B choices
    expect($quiz->matchingChoices)->toHaveCount(5);
    expect($quiz->matchingChoices[0]->choice_letter)->toBe('A');
    expect($quiz->matchingChoices[3]->choice_letter)->toBe('D');
    expect($quiz->matchingChoices[3]->choice_text)->toBe('San Luis');

    // Verify Column A items
    expect($quiz->matchingItems)->toHaveCount(5);
    expect($quiz->matchingItems[0]->item_number)->toBe(1);
    expect($quiz->matchingItems[0]->description)->toContain('coastal town in Aurora');
    expect($quiz->matchingItems[0]->correctChoice->choice_letter)->toBe('D');
    expect($quiz->matchingItems[0]->correctChoice->choice_text)->toBe('San Luis');

    expect($quiz->matchingItems[1]->correctChoice->choice_letter)->toBe('E');
    expect($quiz->matchingItems[2]->correctChoice->choice_letter)->toBe('B');
    expect($quiz->matchingItems[3]->correctChoice->choice_letter)->toBe('A');
    expect($quiz->matchingItems[4]->correctChoice->choice_letter)->toBe('C');
});
