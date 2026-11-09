<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreEvaluationQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherEvaluationQuizController extends Controller
{
    /**
     * Display the form to create a new Evaluation quiz.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/quizes/Evaluation', [
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
     * Store a newly created Evaluation quiz in storage.
     */
    public function store(StoreEvaluationQuizRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'evaluation';
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
                'type' => QuizType::Evaluation,
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'xp_reward' => (int) $validated['xp_reward'],
                'passing_percentage' => (int) $validated['passing_percentage'],
                'time_limit_seconds' => ! empty($validated['time_limit_seconds']) ? (int) $validated['time_limit_seconds'] : null,
                'sort_order' => $nextOrder,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
            ]);

            // Save scenario contexts
            if (! empty($validated['contexts'])) {
                foreach ($validated['contexts'] as $ctxData) {
                    $scen = $ctxData['scenario'] ?? null;
                    if ($scen !== null && trim($scen) !== '') {
                        $quiz->contexts()->create([
                            'scenario' => trim($scen),
                            'hint' => ! empty($ctxData['hint']) ? trim($ctxData['hint']) : null,
                        ]);
                    }
                }
            }

            // Save word helper chips
            if (! empty($validated['word_helpers'])) {
                foreach ($validated['word_helpers'] as $index => $word) {
                    if (trim($word) !== '') {
                        $quiz->evaluationWordHelpers()->create([
                            'word' => trim($word),
                            'sort_order' => $index + 1,
                        ]);
                    }
                }
            }

            // Save evaluation items with Step 1 options & Step 2 blanks
            foreach ($validated['items'] as $index => $itemData) {
                $item = $quiz->evaluationItems()->create([
                    'section_title' => trim($itemData['section_title']),
                    'conflict_type' => ! empty($itemData['conflict_type']) ? trim($itemData['conflict_type']) : null,
                    'question_prompt' => trim($itemData['question_prompt']),
                    'step1_prompt' => ! empty($itemData['step1_prompt']) ? trim($itemData['step1_prompt']) : 'Step 1: Pick one option:',
                    'sentence_template' => trim($itemData['sentence_template']),
                    'hint' => ! empty($itemData['hint']) ? trim($itemData['hint']) : null,
                    'points' => (int) ($itemData['points'] ?? 25),
                    'sort_order' => $index + 1,
                ]);

                // Options
                if (! empty($itemData['options'])) {
                    foreach ($itemData['options'] as $oIdx => $optData) {
                        $item->options()->create([
                            'option_text' => trim($optData['option_text']),
                            'stance_value' => ! empty($optData['stance_value']) ? trim($optData['stance_value']) : trim($optData['option_text']),
                            'is_correct' => (bool) ($optData['is_correct'] ?? true),
                            'sort_order' => $oIdx + 1,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('teachers.modules.show', $module->slug)
            ->with('status', 'Evaluation challenge created successfully!');
    }
}
