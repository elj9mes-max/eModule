<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access ai endpoints', function () {
    $this->getJson(route('teachers.ai.status'))->assertUnauthorized();
    $this->postJson(route('teachers.ai.word-meaning'), ['word' => 'brave'])->assertUnauthorized();
    $this->postJson(route('teachers.ai.lecture-assist'), ['topic' => 'Heroism'])->assertUnauthorized();
    $this->postJson(route('teachers.ai.module-assist'), ['title' => 'Whispering Woods'])->assertUnauthorized();
    $this->postJson(route('teachers.ai.quiz-assist'), ['quiz_type' => 'multiple_choice', 'topic_or_passage' => 'A brave knight'])->assertUnauthorized();
});

test('students cannot access teacher ai endpoints', function () {
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $this->actingAs($student)->getJson(route('teachers.ai.status'))->assertForbidden();
    $this->actingAs($student)->postJson(route('teachers.ai.word-meaning'), ['word' => 'brave'])->assertForbidden();
    $this->actingAs($student)->postJson(route('teachers.ai.lecture-assist'), ['topic' => 'Heroism'])->assertForbidden();
    $this->actingAs($student)->postJson(route('teachers.ai.module-assist'), ['title' => 'Whispering Woods'])->assertForbidden();
    $this->actingAs($student)->postJson(route('teachers.ai.quiz-assist'), ['quiz_type' => 'multiple_choice', 'topic_or_passage' => 'A brave knight'])->assertForbidden();
});

test('teachers can check ai status', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    config()->set('services.gemini.api_key', 'test-key');

    $response = $this->actingAs($teacher)->getJson(route('teachers.ai.status'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'configured' => true,
            'model' => 'gemini-3.5-flash-lite',
        ]);
});

test('teachers can request word meaning with kid-friendly definition', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    config()->set('services.gemini.api_key', 'test-key');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'word' => 'diligent',
                                    'pronunciation' => 'adj. • dil-i-juhnt',
                                    'meaning' => 'Working hard and carefully to get a job done well.',
                                    'example_sentence' => 'The diligent bee collected nectar all afternoon.',
                                    'synonyms' => ['hardworking', 'careful', 'attentive'],
                                    'suggested_color' => 'mint',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teachers.ai.word-meaning'), [
        'word' => 'diligent',
        'context' => 'The diligent bee collected nectar.',
        'grade_level' => 'Grade 3',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.word', 'diligent')
        ->assertJsonPath('data.meaning', 'Working hard and carefully to get a job done well.')
        ->assertJsonPath('data.suggested_color', 'mint');
});

test('teachers can request lecture assistance', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    config()->set('services.gemini.api_key', 'test-key');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'The Whispering Forest Trail',
                                    'description' => 'Explore the mysteries of the forest and decode nature clues.',
                                    'learning_objectives' => ['Learn folklore', 'Identify setting clues'],
                                    'estimated_duration_minutes' => 25,
                                    'xp_reward' => 200,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teachers.ai.lecture-assist'), [
        'topic' => 'Forest Animals and Ecosystems',
        'grade_level' => 'Grade 4',
        'mode' => 'describe',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'The Whispering Forest Trail')
        ->assertJsonPath('data.xp_reward', 200);
});

test('teachers can request quiz assistance', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    config()->set('services.gemini.api_key', 'test-key');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'Reading Quiz: Forest Trail',
                                    'description' => 'Test your reading knowledge.',
                                    'questions' => [
                                        [
                                            'question_text' => 'Where did the story take place?',
                                            'selection_type' => 'single',
                                            'points' => 10,
                                            'hint' => 'Check the beginning',
                                            'explanation' => 'The story begins in Whispering Woods.',
                                            'options' => [
                                                ['option_text' => 'Whispering Woods', 'is_correct' => true, 'feedback' => 'Correct!'],
                                                ['option_text' => 'Under the ocean', 'is_correct' => false, 'feedback' => 'Incorrect'],
                                            ],
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teachers.ai.quiz-assist'), [
        'quiz_type' => 'multiple_choice',
        'topic_or_passage' => 'The students explored Whispering Woods during autumn.',
        'question_count' => 1,
        'grade_level' => 'Grade 3',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Reading Quiz: Forest Trail')
        ->assertJsonCount(1, 'data.questions');
});

test('teachers can request module description assistance based on module title', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    config()->set('services.gemini.api_key', 'test-key');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'description' => 'Embark on a woodland adventure where students decode animal clues and unlock vocabulary secrets hidden deep within the trees!',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teachers.ai.module-assist'), [
        'title' => 'Secrets of the Whispering Woods',
        'grade_level' => 'Grade 3 • Reading Legends',
        'theme' => 'forest',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.description', 'Embark on a woodland adventure where students decode animal clues and unlock vocabulary secrets hidden deep within the trees!');
});

test('returns 422 when api key is not configured', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    config()->set('services.gemini.api_key', '');

    $response = $this->actingAs($teacher)->postJson(route('teachers.ai.word-meaning'), [
        'word' => 'curious',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error_code', 'NOT_CONFIGURED');
});
