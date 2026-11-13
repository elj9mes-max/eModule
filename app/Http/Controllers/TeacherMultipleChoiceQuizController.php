<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreMultipleChoiceQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\Cache\EModuleCacheService;
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

        app(EModuleCacheService::class)->invalidateModule($module->id, bumpCatalog: true);

        return redirect()->route('teachers.modules.show', $module->slug);
    }

    /**
     * Display the specified multiple choice quiz results or inspector.
     */
    public function show(Request $request, Module $module, Quiz $quiz): Response|RedirectResponse
    {
        return app(TeacherQuizResultController::class)->show($request, $module, $quiz);
    }
}
