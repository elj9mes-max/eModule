<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreConflictMapQuizRequest;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherConflictMapQuizController extends Controller
{
    /**
     * Display the form to create a new Conflict Map quiz.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/quizes/ConflictMap', [
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
     * Store a newly created Conflict Map quiz in storage.
     */
    public function store(StoreConflictMapQuizRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'the-conflict-map';
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
                'type' => QuizType::TheConflictMap,
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

            // Save drop zones
            $createdZones = [];
            foreach ($validated['zones'] as $index => $zoneData) {
                $zone = $quiz->mapZones()->create([
                    'conflict_type' => $zoneData['conflict_type'],
                    'label' => $zoneData['label'],
                    'subtitle' => $zoneData['subtitle'] ?? null,
                    'color_theme' => $zoneData['color_theme'] ?? 'blue',
                    'sort_order' => $index + 1,
                ]);
                $createdZones[$zoneData['conflict_type']] = $zone->id;
            }

            // Save event bank cards
            foreach ($validated['events'] as $index => $eventData) {
                $targetType = $eventData['target_conflict_type'];
                $zoneId = $createdZones[$targetType] ?? null;

                $quiz->mapEvents()->create([
                    'zone_id' => $zoneId,
                    'card_letter' => ! empty($eventData['card_letter']) ? trim($eventData['card_letter']) : 'Card '.chr(65 + $index),
                    'event_text' => $eventData['event_text'],
                    'target_conflict_type' => $targetType,
                    'hint' => ! empty($eventData['hint']) ? trim($eventData['hint']) : null,
                    'explanation' => ! empty($eventData['explanation']) ? trim($eventData['explanation']) : null,
                    'points' => (int) ($eventData['points'] ?? 10),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('teachers.modules.show', $module->slug)
            ->with('status', 'The Conflict Map challenge created successfully!');
    }
}
