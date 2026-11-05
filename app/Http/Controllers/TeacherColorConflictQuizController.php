<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreColorConflictQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherColorConflictQuizController extends Controller
{
    /**
     * Display the form to create a new Color the Conflict quiz.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/quizes/ColortheConflict', [
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
     * Store a newly created Color the Conflict quiz in storage.
     */
    public function store(StoreColorConflictQuizRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'color-the-conflict';
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
                'type' => QuizType::ColorTheConflict,
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

            // Save flashcards
            foreach ($validated['cards'] as $index => $cardData) {
                $quiz->conflictCards()->create([
                    'excerpt' => $cardData['excerpt'],
                    'conflict_type' => $cardData['conflict_type'],
                    'hint' => ! empty($cardData['hint']) ? trim($cardData['hint']) : null,
                    'explanation' => ! empty($cardData['explanation']) ? trim($cardData['explanation']) : null,
                    'feedback_incorrect' => ! empty($cardData['feedback_incorrect']) ? trim($cardData['feedback_incorrect']) : null,
                    'points' => (int) ($cardData['points'] ?? 10),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('teachers.modules.show', $module->slug)
            ->with('status', 'Color the Conflict challenge created successfully!');
    }
}
