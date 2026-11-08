<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Http\Requests\StoreModuleRequest;
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
use App\Models\QuizContext;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherModuleController extends Controller
{
    /**
     * Display the form to create a new module.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        return Inertia::render('teachers/create/Module', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    /**
     * Store a newly created module in storage.
     */
    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $baseSlug = Str::slug($validated['title']) ?: 'module';
        $slug = $baseSlug;
        $counter = 1;

        while (Module::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('modules/covers', 'public');
        }

        $nextOrder = ((int) $user->modules()->max('sort_order')) + 1;
        $status = ModuleStatus::from($validated['status']);

        $user->modules()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'grade_level' => $validated['grade_level'] ?? null,
            'description' => $validated['description'] ?? null,
            'theme' => $validated['theme'],
            'icon' => $validated['icon'],
            'cover_image_path' => $coverImagePath,
            'xp_bonus' => (int) $validated['xp_bonus'],
            'boss_name' => $validated['boss_name'] ?? null,
            'max_students' => isset($validated['max_students']) && $validated['max_students'] !== null ? (int) $validated['max_students'] : null,
            'status' => $status,
            'sort_order' => $nextOrder,
            'published_at' => $status === ModuleStatus::Published ? now() : null,
        ]);

        return redirect()->route('teachers.dashboard');
    }

    /**
     * Display the adventure journey map for a specific module.
     */
    public function show(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $module->load([
            'lectures' => fn ($query) => $query->with('contents')->orderBy('sort_order'),
            'quizzes' => fn ($query) => $query->with(['contexts', 'questions.options'])->orderBy('sort_order'),
        ]);

        return Inertia::render('teachers/view/Module', [
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
                'description' => $module->description,
                'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                'theme' => $module->theme->value,
                'icon' => $module->icon,
                'cover_image_url' => $module->coverImageUrl(),
                'xp_bonus' => $module->xp_bonus,
                'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                'max_students' => $module->max_students,
                'status' => $module->status->value,
            ],
            'lectures' => $module->lectures->map(fn (Lecture $lecture): array => [
                'id' => $lecture->id,
                'title' => $lecture->title,
                'slug' => $lecture->slug,
                'description' => $lecture->description,
                'body' => $lecture->body,
                'estimated_duration_minutes' => $lecture->estimated_duration_minutes,
                'xp_reward' => $lecture->xp_reward,
                'sort_order' => $lecture->sort_order,
                'is_published' => $lecture->is_published,
                'contents_count' => $lecture->contents->count(),
                'contents' => $lecture->contents->map(fn (LectureContent $content): array => [
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
                ])->values()->all(),
            ])->values()->all(),
            'quizzes' => $module->quizzes->map(fn (Quiz $quiz): array => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'slug' => $quiz->slug,
                'type' => $quiz->type->value,
                'description' => $quiz->description,
                'reading_passage' => $quiz->contexts->first()?->scenario ?? $quiz->reading_passage,
                'context' => $quiz->contexts->first() ? [
                    'id' => $quiz->contexts->first()->id,
                    'scenario' => $quiz->contexts->first()->scenario,
                    'hint' => $quiz->contexts->first()->hint,
                ] : null,
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
                'questions_count' => $quiz->questions->count(),
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
            ])->values()->all(),
        ]);
    }

    /**
     * Display the unified full-screen Quiz Studio for creating and managing all 6 quiz formats.
     */
    public function quizStudio(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();
        $initialType = (string) $request->query('type', 'multiple-choice');

        $module->load([
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

        return Inertia::render('teachers/create/quizes/QuizStudio', [
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
                'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                'next_sort_order' => $nextOrder,
            ],
            'initialQuizType' => $initialType,
            'existingQuizzes' => $module->quizzes->map(fn (Quiz $quiz): array => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'slug' => $quiz->slug,
                'type' => $quiz->type->value,
                'description' => $quiz->description,
                'xp_reward' => $quiz->xp_reward,
                'passing_percentage' => $quiz->passing_percentage,
                'time_limit_seconds' => $quiz->time_limit_seconds,
                'sort_order' => $quiz->sort_order,
                'is_published' => $quiz->is_published,
                'contexts' => $quiz->contexts->map(fn (QuizContext $ctx): array => [
                    'scenario' => $ctx->scenario,
                    'hint' => $ctx->hint ?? '',
                ])->values()->all(),
                'questions' => $quiz->questions->map(fn (QuizQuestion $q): array => [
                    'question_text' => $q->question_text,
                    'selection_type' => $q->selection_type->value,
                    'passage_context' => $q->passage_context ?? '',
                    'hint' => $q->hint ?? '',
                    'explanation' => $q->explanation ?? '',
                    'points' => $q->points,
                    'options' => $q->options->map(fn (QuizQuestionOption $opt): array => [
                        'option_text' => $opt->option_text,
                        'is_correct' => $opt->is_correct,
                        'feedback' => $opt->feedback ?? '',
                    ])->values()->all(),
                ])->values()->all(),
                'cards' => $quiz->conflictCards->map(fn (ColorConflictCard $c): array => [
                    'excerpt' => $c->excerpt,
                    'conflict_type' => $c->conflict_type->value,
                    'hint' => $c->hint ?? '',
                    'explanation' => $c->explanation ?? '',
                    'feedback_incorrect' => $c->feedback_incorrect ?? '',
                    'points' => $c->points,
                    'sort_order' => $c->sort_order,
                ])->values()->all(),
                'zones' => $quiz->mapZones->map(fn (ConflictMapZone $z): array => [
                    'conflict_type' => $z->conflict_type->value,
                    'label' => $z->label,
                    'subtitle' => $z->subtitle ?? '',
                    'color_theme' => $z->color_theme ?? 'blue',
                    'sort_order' => $z->sort_order,
                ])->values()->all(),
                'events' => $quiz->mapEvents->map(fn (ConflictMapEvent $e): array => [
                    'card_letter' => $e->card_letter,
                    'event_text' => $e->event_text,
                    'target_conflict_type' => $e->target_conflict_type->value,
                    'hint' => $e->hint ?? '',
                    'explanation' => $e->explanation ?? '',
                    'points' => $e->points,
                    'sort_order' => $e->sort_order,
                ])->values()->all(),
                'choices' => $quiz->matchingChoices->map(fn (MatchingChoice $mc): array => [
                    'choice_letter' => $mc->choice_letter,
                    'choice_text' => $mc->choice_text,
                ])->values()->all(),
                'matching_items' => $quiz->matchingItems->map(fn (MatchingItem $mi): array => [
                    'item_number' => $mi->item_number,
                    'description' => $mi->description,
                    'correct_choice_letter' => $mi->correctChoice?->choice_letter ?? 'A',
                    'hint' => $mi->hint ?? '',
                    'explanation' => $mi->explanation ?? '',
                    'points' => $mi->points,
                    'sort_order' => $mi->sort_order,
                ])->values()->all(),
                'word_helpers' => $quiz->evaluationWordHelpers->map(fn (EvaluationWordHelper $wh): string => $wh->word)->values()->all(),
                'evaluation_items' => $quiz->evaluationItems->map(fn (EvaluationItem $ei): array => [
                    'section_title' => $ei->section_title,
                    'conflict_type' => $ei->conflict_type ?? 'character_vs_character',
                    'question_prompt' => $ei->question_prompt,
                    'step1_prompt' => $ei->step1_prompt ?? 'Step 1: Pick one option:',
                    'sentence_template' => $ei->sentence_template,
                    'hint' => $ei->hint ?? '',
                    'points' => $ei->points,
                    'sort_order' => $ei->sort_order,
                    'options' => $ei->options->map(fn (EvaluationItemOption $eo): array => [
                        'option_text' => $eo->option_text,
                        'stance_value' => $eo->stance_value ?? '',
                        'is_correct' => $eo->is_correct,
                    ])->values()->all(),
                ])->values()->all(),
                'prompts' => $quiz->reflectionPrompts->map(fn (ReflectionPrompt $rp): array => [
                    'prompt_text' => $rp->prompt_text,
                    'max_words' => $rp->max_words,
                    'min_words' => $rp->min_words,
                    'guiding_hint' => $rp->guiding_hint ?? '',
                    'starter_sentence' => $rp->starter_sentence ?? '',
                    'points' => $rp->points,
                    'sort_order' => $rp->sort_order,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    /**
     * Delete a quiz milestone from the module.
     */
    public function destroyQuiz(Request $request, Module $module, Quiz $quiz): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $quiz->module_id !== $module->id) {
            return redirect()->route('teachers.dashboard');
        }

        $quiz->delete();

        return redirect()->route('teachers.quizzes.studio', $module->slug)
            ->with('status', 'Quiz deleted from module.');
    }
}
