<?php

namespace App\Http\Controllers;

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
use App\Models\QuizAttemptAnswer;
use App\Models\QuizContext;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use App\Services\Cache\EModuleCacheService;
use App\Services\Gamification\LevelProgressionService;
use App\Services\QuizAnswerContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeacherQuizResultController extends Controller
{
    public function __construct(
        private readonly EModuleCacheService $cache,
        private readonly QuizAnswerContextBuilder $contextBuilder,
        private readonly LevelProgressionService $progressionService,
    ) {}

    /**
     * Display the quiz analytics, blueprint, student results, and grading interface.
     */
    public function show(Request $request, Module $module, Quiz $quiz): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $quiz->module_id !== $module->id) {
            return redirect()->route('teachers.dashboard');
        }

        $this->cache->loadRelationsForQuizType($quiz);

        $primaryContext = $quiz->contexts->first();
        $analytics = $this->cache->getTeacherQuizAnalytics($quiz->id);

        // Retrieve student attempts for this quiz
        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->with(['user:id,name,email', 'answers'])
            ->latest('id')
            ->get();

        $dictionaries = $this->contextBuilder->dictionariesFromQuiz($quiz);
        $mcqQuestions = $dictionaries['mcqQuestions'];
        $conflictCards = $dictionaries['conflictCards'];
        $mapZones = $quiz->relationLoaded('mapZones') ? $quiz->mapZones : collect();
        $mapEvents = $dictionaries['mapEvents'];
        $matchingChoices = $quiz->relationLoaded('matchingChoices') ? $quiz->matchingChoices : collect();
        $matchingItems = $dictionaries['matchingItems'];
        $evalItems = $dictionaries['evaluationItems'];
        $refPrompts = $dictionaries['reflectionPrompts'];

        $mappedSubmissions = $attempts->map(function (QuizAttempt $attempt) use ($dictionaries): array {
            $isPending = $attempt->status === 'pending_review';

            return [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'status' => $attempt->status,
                'is_pending_review' => $isPending,
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'percentage' => (float) $attempt->percentage,
                'stars' => $attempt->stars,
                'is_passed' => $attempt->is_passed,
                'xp_earned' => $attempt->xp_earned,
                'time_spent_seconds' => $attempt->time_spent_seconds,
                'started_at' => $attempt->started_at?->format('M d, Y h:i A'),
                'completed_at' => $attempt->completed_at?->format('M d, Y h:i A') ?? $attempt->created_at?->format('M d, Y h:i A'),
                'teacher_feedback' => $attempt->teacher_feedback,
                'student' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email,
                ],
                'answers' => $attempt->answers->map(
                    fn (QuizAttemptAnswer $ans): array => $this->contextBuilder->formatAnswer($ans, $dictionaries)
                )->values()->all(),
            ];
        })->values()->all();

        return Inertia::render('teachers/view/quizes/QuizResult', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'icon' => $module->icon,
                'theme' => $module->theme->value,
                'grade_level' => $module->grade_level,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'slug' => $quiz->slug,
                'type' => $quiz->type->value,
                'description' => $quiz->description,
                'reading_passage' => $primaryContext?->scenario ?? $quiz->reading_passage,
                'contexts' => $quiz->contexts->map(fn (QuizContext $ctx): array => [
                    'id' => $ctx->id,
                    'scenario' => $ctx->scenario,
                    'hint' => $ctx->hint,
                ])->values()->all(),
                'xp_reward' => $quiz->xp_reward,
                'passing_percentage' => $quiz->passing_percentage,
                'time_limit_seconds' => $quiz->time_limit_seconds,
                'sort_order' => $quiz->sort_order,
                'is_published' => $quiz->is_published,
                'questions_count' => match ($quiz->type) {
                    QuizType::MultipleChoice => $mcqQuestions->count(),
                    QuizType::ColorTheConflict => $conflictCards->count(),
                    QuizType::TheConflictMap => $mapEvents->count(),
                    QuizType::MatchingType => $matchingItems->count(),
                    QuizType::Evaluation => $evalItems->count(),
                    QuizType::Reflection => $refPrompts->count(),
                },
                'questions' => $mcqQuestions->values()->map(fn (QuizQuestion $question): array => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'selection_type' => $question->selection_type->value,
                    'passage_context' => $question->passage_context,
                    'hint' => $question->hint,
                    'explanation' => $question->explanation,
                    'points' => $question->points,
                    'sort_order' => $question->sort_order,
                    'options' => $question->options->map(fn (QuizQuestionOption $option): array => [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct,
                        'feedback' => $option->feedback,
                        'sort_order' => $option->sort_order,
                    ])->values()->all(),
                ])->all(),
                'conflict_cards' => $conflictCards->values()->map(fn (ColorConflictCard $card): array => [
                    'id' => $card->id,
                    'excerpt' => $card->excerpt,
                    'conflict_type' => $card->conflict_type->value,
                    'conflict_label' => $card->conflict_type->label(),
                    'hint' => $card->hint,
                    'explanation' => $card->explanation,
                    'points' => $card->points,
                    'sort_order' => $card->sort_order,
                ])->all(),
                'map_zones' => $mapZones->values()->map(fn (ConflictMapZone $zone): array => [
                    'id' => $zone->id,
                    'conflict_type' => $zone->conflict_type->value,
                    'label' => $zone->label,
                    'subtitle' => $zone->subtitle,
                    'color_theme' => $zone->color_theme,
                    'sort_order' => $zone->sort_order,
                ])->all(),
                'map_events' => $mapEvents->values()->map(fn (ConflictMapEvent $event): array => [
                    'id' => $event->id,
                    'card_letter' => $event->card_letter,
                    'event_text' => $event->event_text,
                    'target_conflict_type' => $event->target_conflict_type->value,
                    'target_conflict_label' => $event->target_conflict_type->label(),
                    'hint' => $event->hint,
                    'explanation' => $event->explanation,
                    'points' => $event->points,
                    'sort_order' => $event->sort_order,
                ])->all(),
                'matching_choices' => $matchingChoices->values()->map(fn (MatchingChoice $choice): array => [
                    'id' => $choice->id,
                    'choice_letter' => $choice->choice_letter,
                    'choice_text' => $choice->choice_text,
                    'sort_order' => $choice->sort_order,
                ])->all(),
                'matching_items' => $matchingItems->values()->map(fn (MatchingItem $item): array => [
                    'id' => $item->id,
                    'item_number' => $item->item_number,
                    'description' => $item->description,
                    'correct_letter' => $item->correctChoice?->choice_letter,
                    'correct_choice_text' => $item->correctChoice?->choice_text,
                    'hint' => $item->hint,
                    'explanation' => $item->explanation,
                    'points' => $item->points,
                    'sort_order' => $item->sort_order,
                ])->all(),
                'evaluation_items' => $evalItems->values()->map(fn (EvaluationItem $item): array => [
                    'id' => $item->id,
                    'section_title' => $item->section_title,
                    'conflict_type' => $item->conflict_type,
                    'question_prompt' => $item->question_prompt,
                    'step1_prompt' => $item->step1_prompt,
                    'sentence_template' => $item->sentence_template,
                    'points' => $item->points,
                    'sort_order' => $item->sort_order,
                    'options' => $item->options->map(fn (EvaluationItemOption $opt): array => [
                        'id' => $opt->id,
                        'option_text' => $opt->option_text,
                        'stance_value' => $opt->stance_value,
                        'is_correct' => (bool) $opt->is_correct,
                    ])->values()->all(),
                ])->all(),
                'reflection_prompts' => $refPrompts->values()->map(fn (ReflectionPrompt $prompt): array => [
                    'id' => $prompt->id,
                    'prompt_text' => $prompt->prompt_text,
                    'min_words' => $prompt->min_words,
                    'max_words' => $prompt->max_words,
                    'points' => $prompt->points,
                    'sort_order' => $prompt->sort_order,
                ])->all(),
            ],
            'analytics' => $analytics,
            'submissions' => $mappedSubmissions,
        ]);
    }

    /**
     * Submit teacher grades and feedback for an attempt from the QuizResult inspector.
     */
    public function grade(Request $request, Module $module, Quiz $quiz, QuizAttempt $attempt): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $quiz->module_id !== $module->id || $attempt->quiz_id !== $quiz->id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.id' => ['required', 'integer'],
            'answers.*.points_earned' => ['required', 'integer', 'min:0'],
            'answers.*.teacher_notes' => ['nullable', 'string', 'max:1000'],
            'teacher_feedback' => ['nullable', 'string', 'max:2500'],
        ]);

        DB::transaction(function () use ($attempt, $quiz, $validated): void {
            $answersById = collect($validated['answers'])->keyBy('id');

            $attemptAnswers = $attempt->answers()->whereIn('id', $answersById->keys())->get();

            $totalEarned = 0;
            $totalPossible = 0;

            foreach ($attemptAnswers as $ans) {
                $payload = $answersById->get($ans->id);
                $pts = (int) ($payload['points_earned'] ?? 0);
                $pts = max(0, min($pts, $ans->points_possible));
                $isCorrect = $pts > 0;

                $ans->update([
                    'points_earned' => $pts,
                    'is_correct' => $isCorrect,
                    'teacher_notes' => $payload['teacher_notes'] ?? null,
                ]);

                $totalEarned += $pts;
                $totalPossible += $ans->points_possible;
            }

            $safeMax = max(1, $attempt->max_score ?: $totalPossible);
            $percentage = round(($totalEarned / $safeMax) * 100, 2);
            $passThreshold = (float) ($quiz->passing_percentage ?? 70);
            $isPassed = $percentage >= $passThreshold;

            $stars = 0;
            if ($percentage >= 90) {
                $stars = 3;
            } elseif ($percentage >= $passThreshold) {
                $stars = 2;
            } elseif ($percentage >= 40) {
                $stars = 1;
            }

            $baseXp = (int) ($quiz->xp_reward ?? 100);
            $xpEarned = (int) round(($percentage / 100) * $baseXp);

            $attempt->update([
                'status' => 'completed',
                'score' => $totalEarned,
                'max_score' => $safeMax,
                'percentage' => $percentage,
                'stars' => $stars,
                'is_passed' => $isPassed,
                'xp_earned' => $xpEarned,
                'teacher_feedback' => $validated['teacher_feedback'] ?? null,
                'completed_at' => now(),
            ]);
        });

        $this->progressionService->recalculateStudentProfile((int) $attempt->user_id);

        $this->cache->invalidateAttemptProgress(
            (int) $attempt->user_id,
            (int) $module->id,
            (int) $user->id,
            (int) $quiz->id,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Grade successfully saved and published!',
                'attempt' => [
                    'id' => $attempt->id,
                    'status' => 'completed',
                    'score' => $attempt->score,
                    'max_score' => $attempt->max_score,
                    'percentage' => (float) $attempt->percentage,
                    'stars' => $attempt->stars,
                    'is_passed' => $attempt->is_passed,
                    'xp_earned' => $attempt->xp_earned,
                    'teacher_feedback' => $attempt->teacher_feedback,
                ],
            ]);
        }

        return back()->with('status', 'Grade successfully saved and published!');
    }
}
