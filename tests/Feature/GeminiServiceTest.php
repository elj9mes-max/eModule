<?php

use App\Exceptions\Gemini\GeminiApiException;
use App\Exceptions\Gemini\GeminiNotConfiguredException;
use App\Exceptions\Gemini\GeminiQuotaExceededException;
use App\Services\Ai\GeminiService;
use Illuminate\Support\Facades\Http;

test('it detects when api key is not configured', function () {
    config()->set('services.gemini.api_key', '');
    $service = new GeminiService(apiKey: '');

    expect($service->isConfigured())->toBeFalse();

    $this->expectException(GeminiNotConfiguredException::class);
    $service->defineWord('luminous');
});

test('it defines a word and caches the result', function () {
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'word' => 'luminous',
                                    'pronunciation' => 'adj. • loo-muh-nuhs',
                                    'meaning' => 'Shining or glowing brightly, especially in the dark.',
                                    'example_sentence' => 'The moon cast a luminous beam over the lake.',
                                    'synonyms' => ['glowing', 'radiant', 'bright'],
                                    'suggested_color' => 'gold',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new GeminiService(apiKey: 'test-api-key');
    expect($service->isConfigured())->toBeTrue();

    $result = $service->defineWord('luminous', 'The moon cast a luminous beam.', 'Grade 3');

    expect($result['word'])->toBe('luminous')
        ->and($result['meaning'])->toContain('glowing')
        ->and($result['pronunciation'])->toBe('adj. • loo-muh-nuhs')
        ->and($result['synonyms'])->toHaveCount(3);

    // Second call should come from cache without hitting Http again
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([], 500),
    ]);

    $cached = $service->defineWord('luminous', 'Any context', 'Grade 3');
    expect($cached['word'])->toBe('luminous');
});

test('it generates lecture assistance in describe mode', function () {
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'Echoes of the Enchanted Forest',
                                    'description' => 'Students follow Maya into the woods to discover ancient folklore.',
                                    'learning_objectives' => [
                                        'Identify character motivation',
                                        'Decipher vocabulary in context',
                                    ],
                                    'estimated_duration_minutes' => 20,
                                    'xp_reward' => 150,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new GeminiService(apiKey: 'test-api-key');
    $result = $service->generateLectureAssist('Folklore and Nature', 'Grade 4', 'describe');

    expect($result['title'])->toBe('Echoes of the Enchanted Forest')
        ->and($result['description'])->toContain('folklore')
        ->and($result['xp_reward'])->toBe(150);
});

test('it generates multiple choice quiz questions', function () {
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'Comprehension Check: Forest Secrets',
                                    'description' => 'Answer questions to unlock the secret path.',
                                    'questions' => [
                                        [
                                            'question_text' => 'What did Maya discover behind the waterfall?',
                                            'points' => 10,
                                            'hint' => 'Look at paragraph 3',
                                            'explanation' => 'Maya uncovered the silver key behind the mossy stone.',
                                            'options' => [
                                                ['option_text' => 'A silver key', 'is_correct' => true, 'feedback' => 'Correct!'],
                                                ['option_text' => 'A dragon', 'is_correct' => false, 'feedback' => 'Incorrect'],
                                                ['option_text' => 'A backpack', 'is_correct' => false, 'feedback' => 'Incorrect'],
                                                ['option_text' => 'An old boat', 'is_correct' => false, 'feedback' => 'Incorrect'],
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

    $service = new GeminiService(apiKey: 'test-api-key');
    $result = $service->generateQuizAssist('multiple_choice', 'Maya and the Waterfall', 1, 'Grade 3');

    expect($result['title'])->toContain('Comprehension Check')
        ->and($result['questions'])->toHaveCount(1)
        ->and($result['questions'][0]['options'])->toHaveCount(4);
});

test('it throws GeminiQuotaExceededException on rate limit status 429', function () {
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'error' => [
                'message' => 'Quota exceeded for quota metric GenerateContentRequestsPerMinute',
                'status' => 'RESOURCE_EXHAUSTED',
            ],
        ], 429),
    ]);

    $service = new GeminiService(apiKey: 'test-api-key');

    $this->expectException(GeminiQuotaExceededException::class);
    $service->defineWord('persevere');
});

test('it throws GeminiApiException when API returns server error', function () {
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'error' => [
                'message' => 'Internal server error occurred',
            ],
        ], 500),
    ]);

    $service = new GeminiService(apiKey: 'test-api-key');

    $this->expectException(GeminiApiException::class);
    $service->defineWord('challenge');
});

test('it correctly resolves ssl verify configuration', function () {
    $serviceDisabled = new GeminiService(apiKey: 'test-key', sslVerify: false);
    expect($serviceDisabled->resolveSslVerifyOption())->toBeFalse();

    $serviceDefault = new GeminiService(apiKey: 'test-key');
    $resolved = $serviceDefault->resolveSslVerifyOption();
    expect($resolved)->not->toBeNull()
        ->and($resolved === true || (is_string($resolved) && file_exists($resolved)))->toBeTrue();
});
