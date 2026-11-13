<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreMatchingTypeQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherMatchingTypeQuizController extends Controller
{
    /**
     * Display the form to create a new Matching Type quiz.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/quizes/MatchingType', [
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
     * Store a newly created Matching Type quiz in storage.
     */
    public function store(StoreMatchingTypeQuizRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'matching-type';
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
                'type' => QuizType::MatchingType,
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

            // Save Column B choices
            $choicesMap = [];
            foreach ($validated['choices'] as $index => $choiceData) {
                $choice = $quiz->matchingChoices()->create([
                    'choice_letter' => trim($choiceData['choice_letter']),
                    'choice_text' => trim($choiceData['choice_text']),
                    'sort_order' => $index + 1,
                ]);
                $choicesMap[trim($choiceData['choice_letter'])] = $choice->id;
            }

            // Save Column A description items
            foreach ($validated['items'] as $index => $itemData) {
                $letter = trim($itemData['correct_choice_letter']);
                $choiceId = $choicesMap[$letter] ?? array_values($choicesMap)[0];

                $quiz->matchingItems()->create([
                    'correct_choice_id' => $choiceId,
                    'item_number' => $index + 1,
                    'description' => trim($itemData['description']),
                    'hint' => ! empty($itemData['hint']) ? trim($itemData['hint']) : null,
                    'explanation' => ! empty($itemData['explanation']) ? trim($itemData['explanation']) : null,
                    'points' => (int) ($itemData['points'] ?? 10),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        app(EModuleCacheService::class)->invalidateModule($module->id, bumpCatalog: true);

        app(EModuleCacheService::class)->invalidateModule($module->id, bumpCatalog: true);

        return redirect()->route('teachers.modules.show', $module->slug)
            ->with('status', 'Matching Type quiz created successfully!');
    }
}
