<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Services\Cache\EModuleCacheService;
use App\Services\Gamification\LevelProgressionService;
use App\Services\QuizAnswerContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeacherGradingController extends Controller
{
    public function __construct(
        private readonly EModuleCacheService $cache,
        private readonly QuizAnswerContextBuilder $contextBuilder,
        private readonly LevelProgressionService $progressionService,
    ) {}

    /**
     * Display the list of student submissions for teacher grading and review.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $teacherModules = $this->cache->getTeacherModuleSummaries($user->id);
        $teacherModuleIds = array_column($teacherModules, 'id');

        $statusFilter = (string) $request->input('status', 'pending_review');
        $moduleFilter = $request->input('module');
        $typeFilter = (string) $request->input('type', 'all');
        $searchFilter = trim((string) $request->input('search', ''));

        $stats = $this->cache->getTeacherGradingStats($user->id);

        $quizIdsSubquery = Quiz::query()
            ->select('id')
            ->whereIn('module_id', $teacherModuleIds);

        if (! empty($moduleFilter)) {
            $quizIdsSubquery->whereHas('module', function ($q) use ($moduleFilter): void {
                if (is_numeric($moduleFilter)) {
                    $q->where('id', (int) $moduleFilter);
                } else {
                    $q->where('slug', $moduleFilter);
                }
            });
        }

        if ($typeFilter !== '' && $typeFilter !== 'all') {
            $quizIdsSubquery->where('type', $typeFilter);
        }

        $baseQuery = QuizAttempt::query()
            ->whereIn('quiz_id', $quizIdsSubquery);

        if ($statusFilter !== 'all') {
            $baseQuery->where('status', $statusFilter);
        }

        if ($searchFilter !== '') {
            $like = '%'.$searchFilter.'%';
            $baseQuery->where(function ($query) use ($like): void {
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHas('quiz', fn ($q) => $q->where('title', 'like', $like))
                    ->orWhereHas('quiz.module', fn ($q) => $q->where('title', 'like', $like));
            });
        }

        $paginatedAttempts = $baseQuery
            ->with([
                'user:id,name,email',
                'quiz:id,module_id,title,slug,type,passing_percentage,xp_reward',
                'quiz.module:id,title,slug',
                'answers',
            ])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $attempts = $paginatedAttempts->getCollection();

        $dictionaries = $this->contextBuilder->dictionariesFromAttempts($attempts);

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
                'answers' => $attempt->answers->map(
                    fn (QuizAttemptAnswer $ans): array => $this->contextBuilder->formatAnswer($ans, $dictionaries)
                )->values()->all(),
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
            'modules' => $teacherModules,
            'filters' => [
                'status' => $statusFilter,
                'module' => $moduleFilter,
                'type' => $typeFilter,
                'search' => $searchFilter,
            ],
            'stats' => $stats,
            'pagination' => [
                'current_page' => $paginatedAttempts->currentPage(),
                'last_page' => $paginatedAttempts->lastPage(),
                'per_page' => $paginatedAttempts->perPage(),
                'total' => $paginatedAttempts->total(),
                'from' => $paginatedAttempts->firstItem(),
                'to' => $paginatedAttempts->lastItem(),
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

        $this->progressionService->recalculateStudentProfile((int) $attempt->user_id);

        $this->cache->invalidateAttemptProgress(
            (int) $attempt->user_id,
            (int) $attempt->quiz->module_id,
            (int) $user->id,
            (int) $attempt->quiz_id,
        );

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
