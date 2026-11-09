<?php

namespace App\Http\Controllers;

use App\Models\EvaluationItem;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\ReflectionPrompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeacherGradingController extends Controller
{
    /**
     * Display the list of student submissions for teacher grading and review.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $teacherModules = $user->modules()->select('id', 'title', 'slug')->get();
        $teacherModuleIds = $teacherModules->pluck('id');

        $statusFilter = (string) $request->input('status', 'pending_review');
        $moduleFilter = $request->input('module');
        $typeFilter = $request->input('type', 'all');

        $baseQuery = QuizAttempt::query()
            ->whereHas('quiz', fn ($q) => $q->whereIn('module_id', $teacherModuleIds));

        $pendingCount = (clone $baseQuery)->where('status', 'pending_review')->count();
        $gradedCount = (clone $baseQuery)->where('status', 'completed')->count();
        $totalCount = (clone $baseQuery)->count();

        // If user requested a status filter, apply it (unless 'all')
        if ($statusFilter !== 'all') {
            $baseQuery->where('status', $statusFilter);
        }

        if (! empty($moduleFilter)) {
            $baseQuery->whereHas('quiz.module', function ($q) use ($moduleFilter): void {
                if (is_numeric($moduleFilter)) {
                    $q->where('id', (int) $moduleFilter);
                } else {
                    $q->where('slug', $moduleFilter);
                }
            });
        }

        if (! empty($typeFilter) && $typeFilter !== 'all') {
            $baseQuery->whereHas('quiz', fn ($q) => $q->where('type', $typeFilter));
        }

        $attempts = $baseQuery
            ->with([
                'user',
                'quiz.module',
                'answers',
            ])
            ->latest('id')
            ->get();

        // Pre-fetch prompts and items for answers
        $evaluationItemIds = $attempts->flatMap->answers
            ->where('item_type', 'evaluation')
            ->pluck('item_id')
            ->unique();
        $evaluationItems = EvaluationItem::query()
            ->whereIn('id', $evaluationItemIds)
            ->get()
            ->keyBy('id');

        $reflectionPromptIds = $attempts->flatMap->answers
            ->where('item_type', 'reflection')
            ->pluck('item_id')
            ->unique();
        $reflectionPrompts = ReflectionPrompt::query()
            ->whereIn('id', $reflectionPromptIds)
            ->get()
            ->keyBy('id');

        $mappedSubmissions = $attempts->map(function (QuizAttempt $attempt) use ($evaluationItems, $reflectionPrompts): array {
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
                'completed_at' => $attempt->completed_at?->format('M d, Y h:i A'),
                'teacher_feedback' => $attempt->teacher_feedback,
                'student' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email,
                ],
                'quiz' => [
                    'id' => $attempt->quiz->id,
                    'title' => $attempt->quiz->title,
                    'slug' => $attempt->quiz->slug,
                    'type' => $attempt->quiz->type->value,
                    'passing_percentage' => (float) ($attempt->quiz->passing_percentage ?? 70),
                    'xp_reward' => (int) ($attempt->quiz->xp_reward ?? 100),
                ],
                'module' => [
                    'id' => $attempt->quiz->module->id,
                    'title' => $attempt->quiz->module->title,
                    'slug' => $attempt->quiz->module->slug,
                ],
                'answers' => $attempt->answers->map(function (QuizAttemptAnswer $ans) use ($evaluationItems, $reflectionPrompts): array {
                    $itemContext = null;

                    if ($ans->item_type === 'evaluation') {
                        $eval = $evaluationItems->get($ans->item_id);
                        if ($eval) {
                            $itemContext = [
                                'section_title' => $eval->section_title,
                                'question_prompt' => $eval->question_prompt,
                                'step1_prompt' => $eval->step1_prompt,
                                'sentence_template' => $eval->sentence_template,
                            ];
                        }
                    } elseif ($ans->item_type === 'reflection') {
                        $ref = $reflectionPrompts->get($ans->item_id);
                        if ($ref) {
                            $itemContext = [
                                'prompt_text' => $ref->prompt_text,
                                'min_words' => $ref->min_words,
                                'max_words' => $ref->max_words,
                            ];
                        }
                    }

                    return [
                        'id' => $ans->id,
                        'item_type' => $ans->item_type,
                        'item_id' => $ans->item_id,
                        'answer_data' => $ans->answer_data,
                        'answer_text' => $ans->answer_text,
                        'is_correct' => $ans->is_correct,
                        'points_earned' => $ans->points_earned,
                        'points_possible' => $ans->points_possible,
                        'teacher_notes' => $ans->teacher_notes,
                        'context' => $itemContext,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return Inertia::render('teachers/Grading', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'submissions' => $mappedSubmissions,
            'modules' => $teacherModules->map(fn (Module $m): array => [
                'id' => $m->id,
                'title' => $m->title,
                'slug' => $m->slug,
            ])->values()->all(),
            'filters' => [
                'status' => $statusFilter,
                'module' => $moduleFilter,
                'type' => $typeFilter,
            ],
            'stats' => [
                'pending_count' => $pendingCount,
                'graded_count' => $gradedCount,
                'total_count' => $totalCount,
            ],
        ]);
    }

    /**
     * Submit teacher grades and feedback for a student's quiz attempt.
     */
    public function grade(Request $request, QuizAttempt $attempt): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $attempt->loadMissing('quiz.module');

        // Verify the teacher owns the module of this quiz
        if ($attempt->quiz->module->user_id !== $user->id) {
            return response()->json(['message' => 'You do not have permission to grade this quiz.'], 403);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.id' => ['required', 'integer'],
            'answers.*.points_earned' => ['required', 'integer', 'min:0'],
            'answers.*.teacher_notes' => ['nullable', 'string', 'max:1000'],
            'teacher_feedback' => ['nullable', 'string', 'max:2500'],
        ]);

        DB::transaction(function () use ($attempt, $validated): void {
            $answersById = collect($validated['answers'])->keyBy('id');

            $attemptAnswers = $attempt->answers()->whereIn('id', $answersById->keys())->get();

            $totalEarned = 0;
            $totalPossible = 0;

            foreach ($attemptAnswers as $ans) {
                $payload = $answersById->get($ans->id);
                $pts = (int) ($payload['points_earned'] ?? 0);
                // Clamp points between 0 and points_possible
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

            // Recalculate attempt overall summary
            $safeMax = max(1, $attempt->max_score ?: $totalPossible);
            $percentage = round(($totalEarned / $safeMax) * 100, 2);
            $passThreshold = (float) ($attempt->quiz->passing_percentage ?? 70);
            $isPassed = $percentage >= $passThreshold;

            $stars = 0;
            if ($percentage >= 90) {
                $stars = 3;
            } elseif ($percentage >= $passThreshold) {
                $stars = 2;
            } elseif ($percentage >= 40) {
                $stars = 1;
            }

            $baseXp = (int) ($attempt->quiz->xp_reward ?? 100);
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
                'message' => 'Submission graded successfully!',
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

        return back()->with('status', 'Submission graded successfully!');
    }
}
