<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreMultipleChoiceQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizContext;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherMultipleChoiceQuizController extends Controller
{
    /**
     * Display the form to create a new multiple choice quiz.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/quizes/MultipleChoice', [
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
                'next_sort_order' => $nextOrder,
            ],
        ]);
    }

    /**
     * Store a newly created multiple choice quiz in storage.
     */
    public function store(StoreMultipleChoiceQuizRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'quiz';
        $slug = $baseSlug;
        $counter = 1;

        while ($module->quizzes()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $nextOrder = $module->nextMilestoneSortOrder();
        $isPublished = (bool) ($validated['is_published'] ?? true);

        DB::transaction(function () use ($module, $validated, $slug, $nextOrder, $isPublished): void {
            /** @var Quiz $quiz */
            $quiz = $module->quizzes()->create([
                'type' => QuizType::MultipleChoice,
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'reading_passage' => $validated['reading_passage'] ?? null,
                'xp_reward' => (int) $validated['xp_reward'],
                'passing_percentage' => (int) $validated['passing_percentage'],
                'time_limit_seconds' => ! empty($validated['time_limit_seconds']) ? (int) $validated['time_limit_seconds'] : null,
                'sort_order' => $nextOrder,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
            ]);

            if (! empty($validated['contexts'])) {
                foreach ($validated['contexts'] as $ctxData) {
                    $scen = $ctxData['scenario'] ?? null;
                    if ($scen !== null && trim($scen) !== '') {
                        $quiz->contexts()->create([
                            'scenario' => $scen,
                            'hint' => $ctxData['hint'] ?? null,
                        ]);
                    }
                }
            } else {
                $scenarioText = $validated['scenario'] ?? $validated['reading_passage'] ?? null;
                $contextHintText = $validated['context_hint'] ?? $validated['hint'] ?? null;

                if ($scenarioText !== null && trim($scenarioText) !== '') {
                    $quiz->contexts()->create([
                        'scenario' => $scenarioText,
                        'hint' => $contextHintText,
                    ]);
                }
            }

            foreach ($validated['questions'] as $qIndex => $qData) {
                $question = $quiz->questions()->create([
                    'question_text' => $qData['question_text'],
                    'selection_type' => $qData['selection_type'],
                    'passage_context' => $qData['passage_context'] ?? null,
                    'hint' => $qData['hint'] ?? null,
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => (int) ($qData['points'] ?? 10),
                    'sort_order' => $qIndex + 1,
                ]);

                foreach ($qData['options'] as $oIndex => $oData) {
                    $question->options()->create([
                        'option_text' => $oData['option_text'],
                        'is_correct' => (bool) $oData['is_correct'],
                        'feedback' => $oData['feedback'] ?? null,
                        'sort_order' => $oIndex + 1,
                    ]);
                }
            }
        });

        return redirect()->route('teachers.modules.show', $module->slug);
    }

    /**
     * Display the specified multiple choice quiz.
     */
    public function show(Request $request, Module $module, Quiz $quiz): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $quiz->module_id !== $module->id) {
            return redirect()->route('teachers.dashboard');
        }

        $quiz->load(['contexts', 'questions.options']);

        $primaryContext = $quiz->contexts->first();

        return Inertia::render('teachers/view/quizes/MultipleChoice', [
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
                'context' => $primaryContext ? [
                    'id' => $primaryContext->id,
                    'scenario' => $primaryContext->scenario,
                    'hint' => $primaryContext->hint,
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
            ],
        ]);
    }
}
