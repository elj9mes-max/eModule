<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreReflectionQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherReflectionQuizController extends Controller
{
    /**
     * Display the form to create a new Reflection quiz.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/quizes/Reflection', [
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
     * Store a newly created Reflection quiz in storage.
     */
    public function store(StoreReflectionQuizRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'reflection';
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
                'type' => QuizType::Reflection,
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

            // Save reflection prompts
            foreach ($validated['prompts'] as $index => $promptData) {
                $quiz->reflectionPrompts()->create([
                    'prompt_text' => trim($promptData['prompt_text']),
                    'max_words' => (int) $promptData['max_words'],
                    'min_words' => ! empty($promptData['min_words']) ? (int) $promptData['min_words'] : null,
                    'guiding_hint' => ! empty($promptData['guiding_hint']) ? trim($promptData['guiding_hint']) : null,
                    'starter_sentence' => ! empty($promptData['starter_sentence']) ? trim($promptData['starter_sentence']) : null,
                    'points' => (int) ($promptData['points'] ?? 50),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('teachers.modules.show', $module->slug)
            ->with('status', 'Reflection activity created successfully!');
    }
}
