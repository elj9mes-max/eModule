<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\ColorConflictCard;
use App\Models\ConflictMapEvent;
use App\Models\ConflictMapZone;
use App\Models\EvaluationItem;
use App\Models\EvaluationItemOption;
use App\Models\EvaluationWordHelper;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\MatchingChoice;
use App\Models\MatchingItem;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizContext;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use App\Models\StudentLectureCompletion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Display the student welcome page shown right after login.
     */
    public function welcome(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Welcome', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    /**
     * Display the student dashboard containing learning modules.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $modules = Module::query()
            ->reorder()
            ->latest('created_at')
            ->latest('id')
            ->withCount(['lectures', 'quizzes'])
            ->get()
            ->map(fn (Module $module): array => [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                'description' => $module->description,
                'theme' => $module->theme->value,
                'icon' => $module->icon,
                'cover_image_url' => $module->coverImageUrl(),
                'xp_bonus' => $module->xp_bonus,
                'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                'lectures_count' => $module->lectures_count,
                'quizzes_count' => $module->quizzes_count,
                'status' => $module->status->value,
                'status_label' => match ($module->status) {
                    ModuleStatus::Published => 'Unlocked',
                    ModuleStatus::Draft => 'New Realm',
                    ModuleStatus::Archived => 'Archived',
                },
                'is_new' => $module->created_at?->isAfter(now()->subDays(2)) ?? false,
            ]);

        return Inertia::render('students/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'modules' => $modules,
        ]);
    }

    /**
     * Display the student leaderboard and ranking page.
     */
    public function ranking(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $students = User::query()
            ->where('role', UserRole::Student)
            ->orderBy('name')
            ->get();

        return Inertia::render('students/Ranking', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'classmates' => $students->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'is_current_user' => $student->id === $user->id,
            ])->values()->all(),
        ]);
    }

    /**
     * Display the student audio and preferences settings page.
     */
    public function settings(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Settings', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    /**
     * Display the interactive 3D module expedition progress map for a student.
     */
    public function progress(Request $request, ?Module $module = null): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $resolvedModule = $module ?? Module::query()->reorder()->latest('created_at')->latest('id')->first();

        if (! $resolvedModule) {
            return Inertia::render('students/Progress', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ]);
        }

        $resolvedModule->load([
            'lectures' => fn ($query) => $query->with('contents')->orderBy('sort_order'),
            'quizzes' => fn ($query) => $query->with([
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
            ])->orderBy('sort_order'),
        ]);

        $completedAttempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $resolvedModule->quizzes->pluck('id'))
            ->whereIn('status', ['completed', 'pending_review'])
            ->get();

        $completedQuizIds = $completedAttempts
            ->pluck('quiz_id')
            ->unique()
            ->values()
            ->all();

        $pendingQuizIds = $completedAttempts
            ->where('status', 'pending_review')
            ->pluck('quiz_id')
            ->unique()
            ->values()
            ->all();

        $completedLectureIds = StudentLectureCompletion::query()
            ->where('user_id', $user->id)
            ->whereIn('lecture_id', $resolvedModule->lectures->pluck('id'))
            ->pluck('lecture_id')
            ->unique()
            ->values()
            ->all();

        return Inertia::render('students/Progress', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'completedQuizIds' => $completedQuizIds,
            'pendingQuizIds' => $pendingQuizIds,
            'completedLectureIds' => $completedLectureIds,
            'module' => [
                'id' => $resolvedModule->id,
                'title' => $resolvedModule->title,
                'slug' => $resolvedModule->slug,
                'description' => $resolvedModule->description,
                'grade_level' => $resolvedModule->grade_level ?? 'All Grades • Reading Quest',
                'theme' => $resolvedModule->theme->value,
                'icon' => $resolvedModule->icon,
                'cover_image_url' => $resolvedModule->coverImageUrl(),
                'xp_bonus' => $resolvedModule->xp_bonus,
                'boss_name' => $resolvedModule->boss_name ?? 'Chapter Guardian',
                'status' => $resolvedModule->status->value,
            ],
            'lectures' => $resolvedModule->lectures->map(fn (Lecture $lecture): array => [
                'id' => $lecture->id,
                'title' => $lecture->title,
                'slug' => $lecture->slug,
                'description' => $lecture->description,
                'body' => $lecture->body,
                'estimated_duration_minutes' => $lecture->estimated_duration_minutes,
                'xp_reward' => $lecture->xp_reward,
                'sort_order' => $lecture->sort_order,
                'contents_count' => $lecture->contents->count(),
                'contents' => $resolvedModule->lectures->find($lecture->id)?->contents->map(fn (LectureContent $content): array => [
                    'id' => $content->id,
                    'type' => $content->type->value,
                    'layout_style' => $content->layout_style ?? 'standard',
                    'title' => $content->title,
                    'content' => $content->content,
                    'media_url' => $content->mediaUrl(),
                    'narration_audio_url' => $content->narrationAudioUrl(),
                    'narration_audio_name' => $content->narration_audio_name,
                    'vocabulary_glosses' => $content->vocabulary_glosses ?? [],
                    'external_url' => $content->external_url,
                    'caption' => $content->caption,
                    'sort_order' => $content->sort_order,
                ])->values()->all() ?? [],
            ])->values()->all(),
            'quizzes' => $resolvedModule->quizzes->map(fn (Quiz $quiz): array => $this->transformQuizForStudent($quiz))->values()->all(),
        ]);
    }

    /**
     * Display the interactive multi-format student quiz arena page.
     */
    public function showQuiz(Request $request, ?Module $module = null, ?Quiz $quiz = null): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $resolvedModule = $module ?? Module::query()->reorder()->latest('created_at')->latest('id')->first();

        if (! $resolvedModule) {
            return Inertia::render('students/views/quiz/Quiz', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ]);
        }

        $resolvedModule->load([
            'quizzes' => fn ($query) => $query->with([
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
            ])->orderBy('sort_order'),
        ]);

        $resolvedQuiz = $quiz && $quiz->module_id === $resolvedModule->id
            ? $resolvedModule->quizzes->firstWhere('id', $quiz->id)
            : $resolvedModule->quizzes->first();

        $existingAttempt = null;
        if ($resolvedQuiz) {
            $latestAttempt = QuizAttempt::query()
                ->where('user_id', $user->id)
                ->where('quiz_id', $resolvedQuiz->id)
                ->whereIn('status', ['completed', 'pending_review'])
                ->latest('id')
                ->first();

            if ($latestAttempt) {
                $isPending = $latestAttempt->status === 'pending_review';

                $existingAttempt = [
                    'id' => $latestAttempt->id,
                    'attempt_number' => $latestAttempt->attempt_number,
                    'status' => $latestAttempt->status,
                    'is_pending_review' => $isPending,
                    'score' => $latestAttempt->score,
                    'max_score' => $latestAttempt->max_score,
                    'percentage' => (float) $latestAttempt->percentage,
                    'stars' => $latestAttempt->stars,
                    'is_passed' => $latestAttempt->is_passed,
                    'xp_earned' => $latestAttempt->xp_earned,
                    'time_spent_seconds' => $latestAttempt->time_spent_seconds,
                    'completed_at' => $latestAttempt->completed_at?->format('M d, Y h:i A'),
                    'teacher_feedback' => $latestAttempt->teacher_feedback,
                ];
            }
        }

        return Inertia::render('students/views/quiz/Quiz', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'module' => [
                'id' => $resolvedModule->id,
                'title' => $resolvedModule->title,
                'slug' => $resolvedModule->slug,
                'description' => $resolvedModule->description,
                'grade_level' => $resolvedModule->grade_level ?? 'All Grades • Reading Quest',
                'theme' => $resolvedModule->theme->value,
                'icon' => $resolvedModule->icon,
                'xp_bonus' => $resolvedModule->xp_bonus,
                'boss_name' => $resolvedModule->boss_name ?? 'Chapter Guardian',
            ],
            'quiz' => $resolvedQuiz ? $this->transformQuizForStudent($resolvedQuiz) : null,
            'existingAttempt' => $existingAttempt,
            'siblingQuizzes' => $resolvedModule->quizzes->map(fn (Quiz $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'type' => $item->type->value,
                'xp_reward' => $item->xp_reward,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
        ]);
    }

    /**
     * Transform a Quiz model with all 6 format payloads for the student UI.
     *
     * @return array<string, mixed>
     */
    private function transformQuizForStudent(Quiz $quiz): array
    {
        $itemsCount = match ($quiz->type->value) {
            'color_the_conflict' => $quiz->conflictCards->count(),
            'the_conflict_map' => $quiz->mapEvents->count(),
            'matching_type' => $quiz->matchingItems->count(),
            'evaluation' => $quiz->evaluationItems->count(),
            'reflection' => $quiz->reflectionPrompts->count(),
            default => $quiz->questions->count(),
        };

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'slug' => $quiz->slug,
            'type' => $quiz->type->value,
            'description' => $quiz->description,
            'reading_passage' => $quiz->contexts->first()?->scenario ?? $quiz->reading_passage,
            'contexts' => $quiz->contexts->map(fn (QuizContext $ctx): array => [
                'id' => $ctx->id,
                'scenario' => $ctx->scenario,
                'hint' => $ctx->hint,
            ])->values()->all(),
            'xp_reward' => $quiz->xp_reward,
            'passing_percentage' => $quiz->passing_percentage,
            'time_limit_seconds' => $quiz->time_limit_seconds,
            'sort_order' => $quiz->sort_order,
            'questions_count' => max(1, $itemsCount),
            'questions' => $quiz->questions->map(fn (QuizQuestion $question): array => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'selection_type' => $question->selection_type->value,
                'passage_context' => $question->passage_context,
                'hint' => $question->hint,
                'points' => $question->points,
                'sort_order' => $question->sort_order,
                'options' => $question->options->map(fn (QuizQuestionOption $option): array => [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'feedback' => $option->feedback,
                    'sort_order' => $option->sort_order,
                ])->values()->all(),
            ])->values()->all(),
            'cards' => $quiz->conflictCards->map(fn (ColorConflictCard $card): array => [
                'id' => $card->id,
                'excerpt' => $card->excerpt,
                'hint' => $card->hint,
                'points' => $card->points,
                'sort_order' => $card->sort_order,
            ])->values()->all(),
            'zones' => $quiz->mapZones->map(fn (ConflictMapZone $zone): array => [
                'id' => $zone->id,
                'conflict_type' => $zone->conflict_type->value,
                'label' => $zone->label,
                'subtitle' => $zone->subtitle,
                'color_theme' => $zone->color_theme ?? 'blue',
                'sort_order' => $zone->sort_order,
            ])->values()->all(),
            'events' => $quiz->mapEvents->map(fn (ConflictMapEvent $event): array => [
                'id' => $event->id,
                'card_letter' => $event->card_letter,
                'event_text' => $event->event_text,
                'hint' => $event->hint,
                'points' => $event->points,
                'sort_order' => $event->sort_order,
            ])->values()->all(),
            'choices' => $quiz->matchingChoices->map(fn (MatchingChoice $choice): array => [
                'id' => $choice->id,
                'choice_letter' => $choice->choice_letter,
                'choice_text' => $choice->choice_text,
            ])->values()->all(),
            'matching_items' => $quiz->matchingItems->map(fn (MatchingItem $item): array => [
                'id' => $item->id,
                'item_number' => $item->item_number,
                'description' => $item->description,
                'hint' => $item->hint,
                'points' => $item->points,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
            'word_helpers' => $quiz->evaluationWordHelpers->map(fn (EvaluationWordHelper $wh): string => $wh->word)->values()->all(),
            'evaluation_items' => $quiz->evaluationItems->map(fn (EvaluationItem $item): array => [
                'id' => $item->id,
                'section_title' => $item->section_title,
                'conflict_type' => $item->conflict_type,
                'question_prompt' => $item->question_prompt,
                'step1_prompt' => $item->step1_prompt ?? 'Step 1: Pick one option:',
                'sentence_template' => $item->sentence_template,
                'hint' => $item->hint,
                'points' => $item->points,
                'sort_order' => $item->sort_order,
                'options' => $item->options->map(fn (EvaluationItemOption $opt): array => [
                    'id' => $opt->id,
                    'option_text' => $opt->option_text,
                    'stance_value' => $opt->stance_value,
                ])->values()->all(),
            ])->values()->all(),
            'prompts' => $quiz->reflectionPrompts->map(fn (ReflectionPrompt $prompt): array => [
                'id' => $prompt->id,
                'prompt_text' => $prompt->prompt_text,
                'min_words' => $prompt->min_words,
                'max_words' => $prompt->max_words,
                'guiding_hint' => $prompt->guiding_hint,
                'starter_sentence' => $prompt->starter_sentence,
                'points' => $prompt->points,
                'sort_order' => $prompt->sort_order,
            ])->values()->all(),
        ];
    }

    /**
     * Display the full-screen 1:1 student lecture content reader matching the teacher's studio layout.
     */
    public function showLecture(Request $request, ?Module $module = null, ?Lecture $lecture = null): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $resolvedModule = $module ?? Module::query()->reorder()->latest('created_at')->latest('id')->first();

        if (! $resolvedModule) {
            return Inertia::render('students/views/modules/LectureContent', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ]);
        }

        $resolvedModule->load([
            'lectures' => fn ($query) => $query->with('contents')->orderBy('sort_order'),
        ]);

        $resolvedLecture = $lecture && $lecture->module_id === $resolvedModule->id
            ? $resolvedModule->lectures->firstWhere('id', $lecture->id)
            : $resolvedModule->lectures->first();

        if (! $resolvedLecture) {
            return Inertia::render('students/views/modules/LectureContent', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'module' => [
                    'id' => $resolvedModule->id,
                    'title' => $resolvedModule->title,
                    'slug' => $resolvedModule->slug,
                    'description' => $resolvedModule->description,
                    'grade_level' => $resolvedModule->grade_level ?? 'All Grades • Reading Quest',
                    'theme' => $resolvedModule->theme->value,
                    'icon' => $resolvedModule->icon,
                    'xp_bonus' => $resolvedModule->xp_bonus,
                    'boss_name' => $resolvedModule->boss_name ?? 'Chapter Guardian',
                ],
            ]);
        }

        return Inertia::render('students/views/modules/LectureContent', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'module' => [
                'id' => $resolvedModule->id,
                'title' => $resolvedModule->title,
                'slug' => $resolvedModule->slug,
                'description' => $resolvedModule->description,
                'grade_level' => $resolvedModule->grade_level ?? 'All Grades • Reading Quest',
                'theme' => $resolvedModule->theme->value,
                'icon' => $resolvedModule->icon,
                'xp_bonus' => $resolvedModule->xp_bonus,
                'boss_name' => $resolvedModule->boss_name ?? 'Chapter Guardian',
            ],
            'lecture' => [
                'id' => $resolvedLecture->id,
                'title' => $resolvedLecture->title,
                'slug' => $resolvedLecture->slug,
                'description' => $resolvedLecture->description,
                'body' => $resolvedLecture->body,
                'estimated_duration_minutes' => $resolvedLecture->estimated_duration_minutes,
                'xp_reward' => $resolvedLecture->xp_reward,
                'sort_order' => $resolvedLecture->sort_order,
                'contents_count' => $resolvedLecture->contents->count(),
            ],
            'contents' => $resolvedLecture->contents->map(fn (LectureContent $content): array => [
                'id' => $content->id,
                'type' => $content->type->value,
                'layout_style' => $content->layout_style ?? 'standard',
                'title' => $content->title,
                'content' => $content->content,
                'media_url' => $content->mediaUrl(),
                'narration_audio_url' => $content->narrationAudioUrl(),
                'narration_audio_name' => $content->narration_audio_name,
                'vocabulary_glosses' => $content->vocabulary_glosses ?? [],
                'external_url' => $content->external_url,
                'duration_seconds' => $content->duration_seconds,
                'caption' => $content->caption,
                'sort_order' => $content->sort_order,
            ])->values()->all(),
            'siblingLectures' => $resolvedModule->lectures->map(fn (Lecture $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'xp_reward' => $item->xp_reward,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
        ]);
    }

    /**
     * Mark a lecture as completed by the student.
     */
    public function completeLecture(Request $request, Module $module, Lecture $lecture): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['message' => 'Only students can mark lectures complete.'], 403);
        }

        $completion = StudentLectureCompletion::firstOrCreate([
            'user_id' => $user->id,
            'lecture_id' => $lecture->id,
        ], [
            'xp_earned' => (int) ($lecture->xp_reward ?? 100),
            'completed_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'completion' => $completion,
            ]);
        }

        return back();
    }
}
