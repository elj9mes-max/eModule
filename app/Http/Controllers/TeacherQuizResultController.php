<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Models\EvaluationItem;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizContext;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeacherQuizResultController extends Controller
{
    /**
     * Display the quiz analytics, blueprint, student results, and grading interface.
     */
    public function show(Request $request, Module $module, Quiz $quiz): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $quiz->module_id !== $module->id) {
            return redirect()->route('teachers.dashboard');
        }

        $quiz->loadMissing([
            'contexts',
            'questions.options',
            'conflictCards',
            'mapZones',
            'mapEvents',
            'matchingChoices',
            'matchingItems.correctChoice',
            'evaluationWordHelpers',
            'evaluationItems.options',
            'reflectionPrompts',
        ]);

        $primaryContext = $quiz->contexts->first();

        // Retrieve all student attempts for this quiz
        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->with(['user', 'answers'])
            ->latest('id')
            ->get();

        // Calculate analytics
        $completedAttempts = $attempts->where('status', 'completed');
        $pendingAttempts = $attempts->where('status', 'pending_review');

        $totalAttempts = $attempts->count();
        $uniqueStudents = $attempts->pluck('user_id')->unique()->count();
        $pendingCount = $pendingAttempts->count();
        $completedCount = $completedAttempts->count();
        $passedCount = $completedAttempts->where('is_passed', true)->count();

        $avgScore = $completedCount > 0 ? round((float) $completedAttempts->avg('score'), 1) : 0;
        $avgPct = $completedCount > 0 ? round((float) $completedAttempts->avg('percentage'), 1) : 0;
        $passRate = $completedCount > 0 ? round(($passedCount / $completedCount) * 100, 1) : 0;
        $highestScore = $completedCount > 0 ? (int) $completedAttempts->max('score') : 0;
        $lowestScore = $completedCount > 0 ? (int) $completedAttempts->min('score') : 0;

        // Context dictionaries for answer mapping
        $quiz->loadMissing('evaluationItems.options');
        $evalItems = $quiz->evaluationItems->keyBy('id');
        $refPrompts = $quiz->reflectionPrompts->keyBy('id');
        $mcqQuestions = $quiz->questions->keyBy('id');
        $conflictCards = $quiz->conflictCards->keyBy('id');
        $mapEvents = $quiz->mapEvents->keyBy('id');
        $matchingItems = $quiz->matchingItems->keyBy('id');

        $mappedSubmissions = $attempts->map(function (QuizAttempt $attempt) use (
            $evalItems,
            $refPrompts,
            $mcqQuestions,
            $conflictCards,
            $mapEvents,
            $matchingItems
        ): array {
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
                'answers' => $attempt->answers->map(function (QuizAttemptAnswer $ans) use (
                    $evalItems,
                    $refPrompts,
                    $mcqQuestions,
                    $conflictCards,
                    $mapEvents,
                    $matchingItems
                ): array {
                    $context = null;
                    $answerData = $ans->answer_data ?? [];

                    if ($ans->item_type === 'evaluation') {
                        $item = $evalItems->get($ans->item_id);
                        if ($item) {
                            $context = [
                                'section_title' => $item->section_title,
                                'question_prompt' => $item->question_prompt,
                                'step1_prompt' => $item->step1_prompt,
                                'sentence_template' => $item->sentence_template,
                                'options' => $item->options->map(fn ($opt): array => [
                                    'id' => $opt->id,
                                    'option_text' => $opt->option_text,
                                    'stance_value' => $opt->stance_value,
                                    'is_correct' => $opt->is_correct,
                                ])->values()->all(),
                            ];

                            if (empty($answerData['stance']) && ! empty($answerData['option_id'])) {
                                $selectedOpt = $item->options->firstWhere('id', (int) $answerData['option_id']);
                                if ($selectedOpt) {
                                    $answerData['stance'] = $selectedOpt->stance_value ?: $selectedOpt->option_text;
                                }
                            }
                        }
                    } elseif ($ans->item_type === 'reflection') {
                        $prompt = $refPrompts->get($ans->item_id);
                        if ($prompt) {
                            $context = [
                                'prompt_text' => $prompt->prompt_text,
                                'min_words' => $prompt->min_words,
                                'max_words' => $prompt->max_words,
                            ];
                        }
                    } elseif ($ans->item_type === 'multiple_choice') {
                        $q = $mcqQuestions->get($ans->item_id);
                        if ($q) {
                            $context = [
                                'question_text' => $q->question_text,
                                'selection_type' => $q->selection_type->value,
                                'hint' => $q->hint,
                                'explanation' => $q->explanation,
                                'options' => $q->options->map(fn (QuizQuestionOption $opt): array => [
                                    'id' => $opt->id,
                                    'option_text' => $opt->option_text,
                                    'is_correct' => $opt->is_correct,
                                ])->values()->all(),
                            ];
                        }
                    } elseif ($ans->item_type === 'color_conflict') {
                        $card = $conflictCards->get($ans->item_id);
                        if ($card) {
                            $context = [
                                'card_text' => $card->card_text,
                                'conflict_type' => $card->conflict_type->value,
                            ];
                        }
                    } elseif ($ans->item_type === 'conflict_map') {
                        $event = $mapEvents->get($ans->item_id);
                        if ($event) {
                            $context = [
                                'event_text' => $event->event_text,
                                'target_conflict_type' => $event->target_conflict_type->value,
                            ];
                        }
                    } elseif ($ans->item_type === 'matching') {
                        $item = $matchingItems->get($ans->item_id);
                        if ($item) {
                            $context = [
                                'prompt_text' => $item->prompt_text,
                                'correct_letter' => $item->correctChoice?->choice_letter,
                            ];
                        }
                    }

                    return [
                        'id' => $ans->id,
                        'item_type' => $ans->item_type,
                        'item_id' => $ans->item_id,
                        'answer_data' => $answerData,
                        'answer_text' => $ans->answer_text,
                        'is_correct' => $ans->is_correct,
                        'points_earned' => $ans->points_earned,
                        'points_possible' => $ans->points_possible,
                        'teacher_notes' => $ans->teacher_notes,
                        'context' => $context,
                    ];
                })->values()->all(),
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
                    QuizType::MultipleChoice => $quiz->questions->count(),
                    QuizType::ColorTheConflict => $quiz->conflictCards->count(),
                    QuizType::TheConflictMap => $quiz->mapEvents->count(),
                    QuizType::MatchingType => $quiz->matchingItems->count(),
                    QuizType::Evaluation => $quiz->evaluationItems->count(),
                    QuizType::Reflection => $quiz->reflectionPrompts->count(),
                },
                'questions' => $quiz->questions->map(fn (QuizQuestion $question): array => [
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
                ])->values()->all(),
                'evaluation_items' => $quiz->evaluationItems->map(fn (EvaluationItem $item): array => [
                    'id' => $item->id,
                    'section_title' => $item->section_title,
                    'conflict_type' => $item->conflict_type,
                    'question_prompt' => $item->question_prompt,
                    'step1_prompt' => $item->step1_prompt,
                    'sentence_template' => $item->sentence_template,
                    'points' => $item->points,
                    'sort_order' => $item->sort_order,
                ])->values()->all(),
                'reflection_prompts' => $quiz->reflectionPrompts->map(fn (ReflectionPrompt $prompt): array => [
                    'id' => $prompt->id,
                    'prompt_text' => $prompt->prompt_text,
                    'min_words' => $prompt->min_words,
                    'max_words' => $prompt->max_words,
                    'points' => $prompt->points,
                    'sort_order' => $prompt->sort_order,
                ])->values()->all(),
            ],
            'analytics' => [
                'total_attempts' => $totalAttempts,
                'unique_students' => $uniqueStudents,
                'pending_review_count' => $pendingCount,
                'graded_count' => $completedCount,
                'passed_count' => $passedCount,
                'average_score' => $avgScore,
                'average_percentage' => $avgPct,
                'pass_rate' => $passRate,
                'highest_score' => $highestScore,
                'lowest_score' => $lowestScore,
            ],
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
