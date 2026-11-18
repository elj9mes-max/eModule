<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Http\Requests\StoreConflictMapQuizRequest;
use App\Http\Requests\UploadConflictMapImageRequest;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\Cache\EModuleCacheService;
use App\Services\ConflictMapImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherConflictMapQuizController extends Controller
{
    public function __construct(private readonly ConflictMapImageService $images) {}

    /**
     * Upload a picture for a Conflict Map event card; the studio keeps the returned path until the quiz is saved.
     */
    public function uploadEventImage(UploadConflictMapImageRequest $request, Module $module): JsonResponse
    {
        $path = $this->images->store($request->file('image'), $module);

        return response()->json([
            'path' => $path,
            'url' => $this->images->url($path),
        ], 201);
    }

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
        $quizId = ! empty($validated['quiz_id']) ? (int) $validated['quiz_id'] : null;
        /** @var Quiz|null $existingQuiz */
        $existingQuiz = $quizId ? $module->quizzes()->find($quizId) : null;

        $isPublished = (bool) ($validated['is_published'] ?? true);
        $previousImagePaths = $existingQuiz?->mapEvents()->pluck('image_path')->all() ?? [];

        DB::transaction(function () use ($module, $validated, $existingQuiz, $isPublished): void {
            if ($existingQuiz) {
                $quiz = $existingQuiz;
                $quiz->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'xp_reward' => (int) $validated['xp_reward'],
                    'stars_per_correct' => isset($validated['stars_per_correct']) ? (int) $validated['stars_per_correct'] : 1,
                    'gems_per_correct' => isset($validated['gems_per_correct']) ? (int) $validated['gems_per_correct'] : 10,
                    'passing_percentage' => (int) $validated['passing_percentage'],
                    'time_limit_seconds' => ! empty($validated['time_limit_seconds']) ? (int) $validated['time_limit_seconds'] : null,
                    'is_published' => $isPublished,
                    'published_at' => $isPublished ? ($quiz->published_at ?? now()) : null,
                ]);

                $quiz->contexts()->delete();
                $quiz->mapZones()->delete();
                $quiz->mapEvents()->delete();
            } else {
                $baseSlug = Str::slug($validated['title']) ?: 'the-conflict-map';
                $slug = $baseSlug;
                $counter = 1;

                while ($module->quizzes()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }

                $nextOrder = $module->nextMilestoneSortOrder();

                /** @var Quiz $quiz */
                $quiz = $module->quizzes()->create([
                    'type' => QuizType::TheConflictMap,
                    'title' => $validated['title'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,
                    'xp_reward' => (int) $validated['xp_reward'],
                    'stars_per_correct' => isset($validated['stars_per_correct']) ? (int) $validated['stars_per_correct'] : 1,
                    'gems_per_correct' => isset($validated['gems_per_correct']) ? (int) $validated['gems_per_correct'] : 10,
                    'passing_percentage' => (int) $validated['passing_percentage'],
                    'time_limit_seconds' => ! empty($validated['time_limit_seconds']) ? (int) $validated['time_limit_seconds'] : null,
                    'sort_order' => $nextOrder,
                    'is_published' => $isPublished,
                    'published_at' => $isPublished ? now() : null,
                ]);
            }

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
                    'event_text' => ! empty($eventData['event_text']) ? trim($eventData['event_text']) : null,
                    'image_path' => $eventData['image_path'] ?? null,
                    'image_disk' => ! empty($eventData['image_path']) ? ConflictMapImageService::DISK : null,
                    'target_conflict_type' => $targetType,
                    'hint' => ! empty($eventData['hint']) ? trim($eventData['hint']) : null,
                    'explanation' => ! empty($eventData['explanation']) ? trim($eventData['explanation']) : null,
                    'points' => (int) ($eventData['points'] ?? 10),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        // Remove pictures that were replaced or removed while editing (kept if a copied quiz still uses them).
        $this->images->deleteUnreferenced($previousImagePaths);

        app(EModuleCacheService::class)->invalidateModule($module->id, bumpCatalog: true);

        return redirect()->route('teachers.modules.show', $module->slug)
            ->with('status', 'The Conflict Map challenge created successfully!');
    }
}
