<?php

namespace App\Services\Ai;

use App\Exceptions\Gemini\GeminiApiException;
use App\Exceptions\Gemini\GeminiNotConfiguredException;
use App\Exceptions\Gemini\GeminiQuotaExceededException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function __construct(
        protected ?string $apiKey = null,
        protected string $model = 'gemini-3.5-flash-lite',
        protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
        protected int $timeout = 30,
        protected int $connectTimeout = 5,
        protected string|bool|null $sslVerify = null
    ) {
        $this->apiKey = $apiKey ?? (string) config('services.gemini.api_key', '');
        $this->model = $model !== 'gemini-3.5-flash-lite' ? $model : (string) config('services.gemini.model', $this->model);
        $this->baseUrl = (string) config('services.gemini.base_url', $this->baseUrl);
        $this->timeout = (int) config('services.gemini.timeout', $this->timeout);
        $this->connectTimeout = (int) config('services.gemini.connect_timeout', $this->connectTimeout);
        $this->sslVerify = $sslVerify ?? config('services.gemini.ssl_verify');
    }

    /**
     * Determine whether the Gemini API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Get kid-friendly definition, pronunciation, part of speech, and example sentence for a word.
     * Caches lookups for 30 days to save quota and provide instantaneous responses.
     *
     * @return array{
     *     word: string,
     *     pronunciation: string,
     *     meaning: string,
     *     example_sentence: string,
     *     synonyms: array<int, string>,
     *     suggested_color: string
     * }
     */
    public function defineWord(string $word, ?string $context = null, ?string $gradeLevel = null): array
    {
        $normalizedWord = strtolower(trim($word));
        $cacheKey = 'gemini:word_meaning:'.md5($normalizedWord.'|'.($gradeLevel ?? 'any'));

        /** @var array{word: string, pronunciation: string, meaning: string, example_sentence: string, synonyms: array<int, string>, suggested_color: string}|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['meaning'])) {
            return $cached;
        }

        $systemInstruction = <<<'INSTRUCTION'
You are an encouraging elementary and middle school English language educator for children.
Given a word or phrase, provide a kid-friendly definition, clear phonetic pronunciation with part of speech, a vivid example sentence, 2-3 child-appropriate synonyms, and a suggested highlight color theme.
Always return strictly a JSON object adhering to this schema:
{
  "word": "original or cleaned word",
  "pronunciation": "e.g., adj. • loo-muh-nuhs",
  "meaning": "Clear, kid-friendly definition (1-2 sentences)",
  "example_sentence": "Engaging contextual sentence",
  "synonyms": ["synonym1", "synonym2"],
  "suggested_color": "one of: gold, mint, sky, rose, lavender"
}
INSTRUCTION;

        $prompt = "Target Word: {$word}\n";
        if ($context) {
            $prompt .= "Story / Sentence Context: {$context}\n";
        }
        if ($gradeLevel) {
            $prompt .= "Target Grade Level: {$gradeLevel}\n";
        }

        $result = $this->generateJson($prompt, $systemInstruction);

        $payload = [
            'word' => (string) ($result['word'] ?? $word),
            'pronunciation' => (string) ($result['pronunciation'] ?? ''),
            'meaning' => (string) ($result['meaning'] ?? ''),
            'example_sentence' => (string) ($result['example_sentence'] ?? ''),
            'synonyms' => is_array($result['synonyms'] ?? null) ? array_values(array_map('strval', $result['synonyms'])) : [],
            'suggested_color' => (string) ($result['suggested_color'] ?? 'gold'),
        ];

        Cache::put($cacheKey, $payload, now()->addDays(30));

        return $payload;
    }

    /**
     * Generate lecture ideas, descriptions, learning goals, or full story passages with vocabulary glosses.
     *
     * @return array<string, mixed>
     */
    public function generateLectureAssist(
        string $topic,
        ?string $gradeLevel = null,
        string $mode = 'describe',
        ?string $additionalContext = null
    ): array {
        $gradeTarget = $gradeLevel ?: 'Grade 3-6 Elementary';

        if ($mode === 'full_story') {
            $systemInstruction = <<<INSTRUCTION
You are a master children's fiction and educational author for an interactive reading gamification platform called WebRead.
Craft an exciting, engaging reading chapter/passage suitable for students ({$gradeTarget}) on the given topic.
Include 3-5 rich, grade-appropriate vocabulary words. Provide their pronunciation and child-friendly meanings.
Return strictly a JSON object conforming to:
{
  "title": "Adventurous chapter title",
  "description": "Engaging 1-2 sentence overview for the lecture node",
  "story_html": "<p>Vivid story paragraph 1...</p><p>Paragraph 2...</p>",
  "estimated_duration_minutes": 15,
  "xp_reward": 150,
  "vocabulary_glosses": [
    {
      "word": "word",
      "pronunciation": "adj. • phonetics",
      "meaning": "kid-friendly definition",
      "color": "#FEF08A"
    }
  ]
}
INSTRUCTION;
        } elseif ($mode === 'vocabulary_list') {
            $systemInstruction = <<<INSTRUCTION
You are an elementary reading curriculum expert. For the given lecture topic and grade level ({$gradeTarget}), extract or propose 4-6 rich vocabulary words that students should learn.
Return strictly a JSON object conforming to:
{
  "vocabulary": [
    {
      "word": "word",
      "pronunciation": "part of speech • phonetics",
      "meaning": "child-friendly definition",
      "example_sentence": "sentence"
    }
  ]
}
INSTRUCTION;
        } else {
            // 'describe' mode
            $systemInstruction = <<<INSTRUCTION
You are an expert reading curriculum architect.
Given a lecture title or topic and target grade level ({$gradeTarget}), generate:
1. A captivating, adventure-themed lecture title.
2. A compelling, child-friendly lecture description (2-3 sentences explaining what students will read and discover).
3. 3 measurable learning objectives (e.g. "Identify the central conflict...", "Use context clues to define unfamiliar words...").
4. Recommended estimated duration in minutes (between 10 and 30).
5. Recommended XP reward (between 100 and 500).

Return strictly a JSON object conforming to:
{
  "title": "Creative milestone title",
  "description": "Compelling summary for student milestone card",
  "learning_objectives": [
    "Objective 1",
    "Objective 2",
    "Objective 3"
  ],
  "estimated_duration_minutes": 20,
  "xp_reward": 150
}
INSTRUCTION;
        }

        $prompt = "Lecture Topic / Idea: {$topic}\nTarget Grade Level: {$gradeTarget}\n";
        if ($additionalContext) {
            $prompt .= "Additional Notes / Context: {$additionalContext}\n";
        }

        return $this->generateJson($prompt, $systemInstruction);
    }

    /**
     * Generate a short, kid-friendly module description based on module title and grade level.
     *
     * @return array<string, mixed>
     */
    public function generateModuleDescription(
        string $title,
        ?string $gradeLevel = null,
        ?string $theme = null
    ): array {
        $gradeTarget = $gradeLevel ?: 'Grade 3-6 Elementary';
        $themeContext = $theme ? "Theme/Biome setting: {$theme}." : '';

        $systemInstruction = <<<INSTRUCTION
You are an expert reading gamification curriculum architect for WebRead.
Given the module title or topic created by a teacher, and target grade level ({$gradeTarget}), craft an engaging, exciting, kid-friendly short description (2 to 4 sentences).
Explain what reading adventures, skills, vocabulary, and interactive lectures the students will experience in this module. {$themeContext}
Keep the tone encouraging, adventurous, and motivating for young learners.

Return strictly a JSON object conforming to:
{
  "description": "Engaging 2-4 sentence module description..."
}
INSTRUCTION;

        $prompt = "Module Title: {$title}\nTarget Grade Level: {$gradeTarget}\n";
        if ($theme) {
            $prompt .= "Module Theme / World: {$theme}\n";
        }

        return $this->generateJson($prompt, $systemInstruction);
    }

    /**
     * Generate structured quiz questions, choices, or reflection prompts based on lecture passage or topic.
     *
     * @return array<string, mixed>
     */
    public function generateQuizAssist(
        string $quizType,
        string $topicOrPassage,
        int $questionCount = 4,
        ?string $gradeLevel = null
    ): array {
        $gradeTarget = $gradeLevel ?: 'Grade 3-6 Elementary';
        $count = max(1, min(10, $questionCount));

        if ($quizType === 'matching_type') {
            $systemInstruction = <<<INSTRUCTION
You are an educational assessment designer. Create a Matching Type quiz for {$gradeTarget} students based on the provided story/topic.
Provide 4-5 distinct choices for Column B (categories, characters, places, or conflict types: e.g., 'Character vs. Character', 'Character vs. Nature', 'Character vs. Society', character names, or settings).
Provide {$count} items for Column A (descriptions or story events) each matching one choice letter in Column B.
Return strictly a JSON object conforming to:
{
  "title": "Matching Type: [Topic]",
  "description": "Directions: Match each event or description in Column A with the correct character, place, or concept in Column B.",
  "choices": [
    { "choice_letter": "A", "choice_text": "Choice A text" },
    { "choice_letter": "B", "choice_text": "Choice B text" }
  ],
  "items": [
    {
      "item_number": 1,
      "description": "Event or clue describing choice...",
      "correct_choice_letter": "A",
      "hint": "Gentle clue for student",
      "explanation": "Why this matches",
      "points": 10
    }
  ]
}
INSTRUCTION;
        } elseif ($quizType === 'reflection') {
            $systemInstruction = <<<INSTRUCTION
You are an educator designing deep, thought-provoking reflective writing prompts for reading comprehension for {$gradeTarget} students.
Create {$count} reflection prompts that encourage students to connect with story themes, character choices, ethics, or personal empathy.
Return strictly a JSON object conforming to:
{
  "title": "Reflection: [Topic]",
  "description": "Take a moment to reflect on what you read and share your thoughts in your own words.",
  "prompts": [
    {
      "prompt_text": "Engaging reflection question",
      "starter_sentence": "Sentence starter to help students begin writing (e.g., 'I think the character decided to...')",
      "guiding_hint": "Clue to think about context and empathy",
      "min_words": 20,
      "max_words": 150,
      "points": 50
    }
  ]
}
INSTRUCTION;
        } elseif ($quizType === 'color_the_conflict') {
            $systemInstruction = <<<INSTRUCTION
You are an English Literature quiz designer focusing on Conflict Types for {$gradeTarget} students.
The three conflict types are:
- 'character_vs_character' (interpersonal dispute)
- 'character_vs_society' (laws, rules, community expectations)
- 'character_vs_nature' (weather, wilderness, physical environment)

Generate {$count} short excerpts or scenario cards from the topic/passage.
Return strictly a JSON object conforming to:
{
  "title": "Color the Conflict: [Topic]",
  "description": "Read each excerpt and identify which of the 3 conflict types it represents.",
  "cards": [
    {
      "excerpt": "Short excerpt showing a clear conflict...",
      "conflict_type": "character_vs_character",
      "hint": "Clue pointing to the opposing forces",
      "explanation": "Detailed explanation of why this fits this conflict type",
      "points": 10
    }
  ]
}
INSTRUCTION;
        } else {
            // Default: 'multiple_choice'
            $systemInstruction = <<<INSTRUCTION
You are an expert reading comprehension quiz designer for {$gradeTarget} students.
Generate {$count} high-quality multiple choice questions based on the provided story passage or topic.
Each question must have:
- Clear question text testing comprehension, inference, or vocabulary in context.
- 4 options with exactly ONE marked as is_correct: true and the other 3 marked is_correct: false.
- Specific feedback for options explaining why it is correct or incorrect.
- A helpful hint and complete educational explanation.
Return strictly a JSON object conforming to:
{
  "title": "Reading Check: [Topic]",
  "description": "Test your reading understanding and earn XP!",
  "reading_passage": "Concise reading passage if one was provided or needed for context",
  "questions": [
    {
      "question_text": "Question text...",
      "selection_type": "single",
      "points": 10,
      "hint": "Helpful reading clue",
      "explanation": "Clear explanation of the correct answer",
      "options": [
        { "option_text": "Correct option", "is_correct": true, "feedback": "Great job! This is correct because..." },
        { "option_text": "Plausible distractor 1", "is_correct": false, "feedback": "Not quite. Remember that..." },
        { "option_text": "Plausible distractor 2", "is_correct": false, "feedback": "Look closely at paragraph 2..." },
        { "option_text": "Plausible distractor 3", "is_correct": false, "feedback": "Check the context clues..." }
      ]
    }
  ]
}
INSTRUCTION;
        }

        $prompt = "Target Grade: {$gradeTarget}\nTopic or Story Passage:\n{$topicOrPassage}\n";

        return $this->generateJson($prompt, $systemInstruction);
    }

    /**
     * Internal caller to Google Gemini REST API.
     * Enforces explicit timeouts, parses candidate output, strips markdown formatting, and decodes JSON.
     *
     * @return array<string, mixed>
     *
     * @throws GeminiNotConfiguredException
     * @throws GeminiQuotaExceededException
     * @throws GeminiApiException
     */
    protected function generateJson(string $prompt, string $systemInstruction): array
    {
        if (! $this->isConfigured()) {
            throw new GeminiNotConfiguredException;
        }

        $endpoint = rtrim($this->baseUrl, '/')."/models/{$this->model}:generateContent";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json',
            ],
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withOptions([
                    'verify' => $this->resolveSslVerifyOption(),
                ])
                ->retry(2, 250, throw: false)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint.'?key='.$this->apiKey, $payload);
        } catch (ConnectionException $e) {
            Log::error('Gemini API connection error: '.$e->getMessage());
            throw new GeminiApiException('Could not connect to Google Gemini API: '.$e->getMessage(), 504);
        } catch (\Throwable $e) {
            Log::error('Gemini API request error: '.$e->getMessage());
            throw new GeminiApiException('Error sending request to Google Gemini API: '.$e->getMessage(), 500);
        }

        if ($response->status() === 429) {
            throw new GeminiQuotaExceededException;
        }

        if ($response->failed()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? $response->body();
            Log::error('Gemini API failed', ['status' => $response->status(), 'message' => $errorMessage]);

            if (str_contains(strtolower($errorMessage), 'quota') || str_contains(strtolower($errorMessage), 'resource_exhausted')) {
                throw new GeminiQuotaExceededException;
            }

            throw new GeminiApiException('Gemini API Error: '.$errorMessage, $response->status(), $response->body());
        }

        $body = $response->json();
        $candidate = $body['candidates'][0] ?? null;

        if (! $candidate) {
            $blockReason = $body['promptFeedback']['blockReason'] ?? 'Unknown safety block';
            throw new GeminiApiException("Gemini did not return any candidates. Reason: {$blockReason}", 422);
        }

        $rawText = $candidate['content']['parts'][0]['text'] ?? null;
        if (! is_string($rawText) || trim($rawText) === '') {
            throw new GeminiApiException('Gemini returned an empty text response.', 422);
        }

        // Clean any code block markers if the model included them
        $cleanJson = trim($rawText);
        if (str_starts_with($cleanJson, '```json')) {
            $cleanJson = substr($cleanJson, 7);
        } elseif (str_starts_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 3);
        }
        if (str_ends_with($cleanJson, '```')) {
            $cleanJson = substr($cleanJson, 0, -3);
        }
        $cleanJson = trim($cleanJson);

        $decoded = json_decode($cleanJson, true);
        if (! is_array($decoded)) {
            Log::warning('Gemini returned unparseable JSON', ['raw' => $rawText]);
            throw new GeminiApiException('Gemini response could not be parsed as valid JSON: '.json_last_error_msg(), 422, $rawText);
        }

        return $decoded;
    }

    /**
     * Resolve the SSL verification option for cURL/Guzzle.
     * Prevents Windows cURL error 60 (unable to get local issuer certificate)
     * by resolving local CA bundles or respecting explicit GEMINI_SSL_VERIFY configuration.
     */
    public function resolveSslVerifyOption(): string|bool
    {
        $configured = $this->sslVerify ?? config('services.gemini.ssl_verify');

        if ($configured === false || $configured === 0 || $configured === 'false' || $configured === '0') {
            return false;
        }

        if (is_string($configured) && $configured !== '' && $configured !== '1' && $configured !== 'true' && file_exists($configured)) {
            return $configured;
        }

        // Check self-contained project cert bundle
        $storageBundle = storage_path('certs/cacert.pem');
        if (file_exists($storageBundle)) {
            return $storageBundle;
        }

        // Check standard Windows CA bundle locations
        $candidates = [
            'C:/php-8.5/extras/ssl/cacert.pem',
            'C:/Program Files/Git/usr/ssl/certs/ca-bundle.crt',
            'C:/Program Files/Git/ucrt64/etc/ssl/certs/ca-bundle.crt',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return true;
    }
}
