<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Http\Requests\StoreModuleRequest;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeacherModuleController extends Controller
{
    public function __construct(
        private readonly EModuleCacheService $cache,
    ) {}

    /**
     * Display the form to create a new module.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $requestedClassroomId = $request->query('class_id');
        $classroom = is_string($requestedClassroomId) && ctype_digit($requestedClassroomId)
            ? collect($this->cache->getTeacherClassrooms($user->id))->firstWhere('id', (int) $requestedClassroomId)
            : null;

        return Inertia::render('teachers/create/Module', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'pending_grading_count' => $this->cache->getTeacherPendingGradingCount($user->id),
            'classroom' => $classroom === null ? null : [
                'id' => $classroom['id'],
                'name' => $classroom['name'],
                'icon' => $classroom['icon'],
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

        /** @var Module $module */
        $module = $user->modules()->create([
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

        $classroom = isset($validated['classroom_id'])
            ? $user->taughtClassrooms()->find($validated['classroom_id'])
            : null;

        if ($classroom !== null) {
            $classroom->assignModule($module);

            return redirect()->route('teachers.dashboard', ['class_id' => $classroom->id]);
        }

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

        $mapData = $this->cache->getTeacherModuleMapData($module);

        return Inertia::render('teachers/view/Module', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'module' => $mapData['module'],
            'lectures' => $mapData['lectures'],
            'quizzes' => $mapData['quizzes'],
            'backLink' => $this->resolveModulesListBackLink($request),
        ]);
    }

    /**
     * Send Back to the modules list exactly as the teacher left it (filtered by classroom, or all).
     *
     * @return array{url: string, label: string}
     */
    private function resolveModulesListBackLink(Request $request): array
    {
        $classrooms = collect($this->cache->getTeacherClassrooms($request->user()->id));
        $sessionKey = TeacherDashboardController::MODULES_FILTER_SESSION_KEY;

        // The classroom list the module was opened from wins; it is part of the URL, so each
        // classroom gets its own (prefetch-cached) copy of this page with the right Back link.
        $requested = $request->query('class_id');
        $classroom = is_string($requested) && ctype_digit($requested)
            ? $classrooms->firstWhere('id', (int) $requested)
            : null;

        if ($classroom !== null && ! $request->prefetch()) {
            $request->session()->put($sessionKey, $classroom['id']);
        }

        // Otherwise (e.g. returning from a lecture or quiz editor) use the last list actually visited.
        if ($classroom === null && $requested === null) {
            $remembered = $request->session()->get($sessionKey);
            $classroom = is_int($remembered) ? $classrooms->firstWhere('id', $remembered) : null;
        }

        if ($classroom === null) {
            return ['url' => route('teachers.dashboard'), 'label' => 'All Modules'];
        }

        return [
            'url' => route('teachers.dashboard', ['class_id' => $classroom['id']]),
            'label' => (string) $classroom['name'],
        ];
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

        $initialType = (string) $request->query('type', 'multiple-choice');
        $studioData = $this->cache->getTeacherQuizStudioData($module);

        return Inertia::render('teachers/create/quizes/QuizStudio', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'module' => $studioData['module'],
            'initialQuizType' => $initialType,
            'existingQuizzes' => $studioData['existingQuizzes'],
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

    /**
     * Update or toggle the publication status of a module.
     */
    public function updateStatus(Request $request, Module $module): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            abort(403, 'Unauthorized module action.');
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $nextStatus = isset($validated['status']) && $validated['status'] !== null
            ? ModuleStatus::from($validated['status'])
            : ($module->status === ModuleStatus::Published ? ModuleStatus::Draft : ModuleStatus::Published);

        $module->status = $nextStatus;
        if ($nextStatus === ModuleStatus::Published) {
            $module->published_at = $module->published_at ?? now();
        } else {
            $module->published_at = null;
        }

        $module->save();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'status' => $module->status->value,
                'published_at' => $module->published_at?->toIso8601String(),
            ]);
        }

        return back();
    }
}
