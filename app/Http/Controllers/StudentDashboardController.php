<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Enums\PixarWorldTheme;
use App\Http\Requests\JoinModuleRequest;
use App\Http\Requests\UpdateStudentThemeRequest;
use App\Models\Classroom;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use App\Services\Gamification\LevelProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly EModuleCacheService $cache,
        private readonly LevelProgressionService $progressionService,
    ) {}

    /**
     * Build a consistent student payload with gamification stats for Inertia responses.
     *
     * @return array<string, mixed>
     */
    private function formatUserPayload(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'ui_theme' => $user->ui_theme?->value ?? PixarWorldTheme::Toybox->value,
        ];

        if ($user->isStudent()) {
            $profile = $user->getOrCreateStudentProfile();
            $levelDetails = $this->progressionService->getLevelDetails($profile->total_xp);

            $payload['gamification'] = [
                'total_xp' => (int) $profile->total_xp,
                'total_stars' => (int) $profile->total_stars,
                'level' => (int) $levelDetails['level'],
                'level_title' => (string) $levelDetails['title'],
                'level_badge' => (string) $levelDetails['badge'],
                'current_level_min_xp' => (int) $levelDetails['current_level_min_xp'],
                'next_level_xp' => (int) $levelDetails['next_level_xp'],
                'xp_in_level' => (int) $levelDetails['xp_in_level'],
                'xp_for_next_level' => (int) $levelDetails['xp_for_next_level'],
                'progress_pct' => (int) $levelDetails['progress_pct'],
                'quizzes_passed' => (int) $profile->quizzes_passed_count,
                'lectures_completed' => (int) $profile->lectures_completed_count,
            ];
        }

        return $payload;
    }

    /**
     * Display the student welcome page shown right after login.
     */
    public function welcome(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Welcome', [
            'user' => $this->formatUserPayload($user),
        ]);
    }

    /**
     * Display the student dashboard containing enrolled learning modules.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Dashboard', [
            'user' => $this->formatUserPayload($user),
            'modules' => $this->cache->getStudentModules($user->id),
        ]);
    }

    /**
     * Enroll a student into a classroom or module using the 6-digit access code.
     */
    public function joinModule(JoinModuleRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Only students can join reading adventures.'], 403);
            }

            return redirect()->route('teachers.dashboard');
        }

        $code = trim((string) $request->validated('join_code'));

        // 1. First check if the code matches an active Classroom
        $classroom = Classroom::query()
            ->where('class_code', $code)
            ->where('is_active', true)
            ->first();

        if ($classroom) {
            $alreadyInClass = $classroom->studentEnrollments()
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyInClass) {
                $msg = 'You have already joined this classroom.';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }

                return back()->withErrors(['join_code' => $msg]);
            }

            if ($classroom->students()->count() >= $classroom->max_students) {
                $msg = "This classroom is at full capacity ({$classroom->max_students} students).";
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }

                return back()->withErrors(['join_code' => $msg]);
            }

            $classroom->enrollStudent($user);

            $this->cache->invalidateStudentEnrollment($user->id);
            $this->cache->invalidateClassroom($classroom->id, $classroom->user_id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'type' => 'classroom',
                    'message' => 'Hooray! You joined classroom "'.$classroom->name.'"!',
                    'classroom' => [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'class_code' => $classroom->class_code,
                    ],
                ]);
            }

            return redirect()->route('students.dashboard')->with('success', 'Hooray! You joined classroom "'.$classroom->name.'" and unlocked your room reading adventures!');
        }

        // 2. Otherwise check if the code matches a standalone published Module
        $module = Module::query()
            ->where('join_code', $code)
            ->where('status', ModuleStatus::Published)
            ->first();

        if (! $module) {
            $error = 'No published adventure or classroom found with code '.$code.'. Please check with your teacher!';
            if ($request->wantsJson()) {
                return response()->json(['message' => $error], 404);
            }

            return back()->withErrors(['join_code' => $error]);
        }

        // Check if already enrolled in module
        $alreadyEnrolled = ModuleEnrollment::query()
            ->where('module_id', $module->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyEnrolled) {
            $msg = 'You have already joined this module.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['join_code' => $msg]);
        }

        // Check capacity if max_students is set
        if ($module->max_students !== null) {
            $enrolledCount = $module->enrollments()->count();
            if ($enrolledCount >= $module->max_students) {
                $msg = 'This reading quest is already at full capacity ('.$module->max_students.' students).';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }

                return back()->withErrors(['join_code' => $msg]);
            }
        }

        ModuleEnrollment::create([
            'module_id' => $module->id,
            'user_id' => $user->id,
            'enrolled_at' => now(),
        ]);

        $this->cache->invalidateStudentEnrollment($user->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'type' => 'module',
                'message' => 'Hooray! You joined "'.$module->title.'"!',
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                ],
            ]);
        }

        return redirect()->route('students.dashboard')->with('success', 'Hooray! You joined "'.$module->title.'"!');
    }

    /**
     * Display the student leaderboard and ranking page (scoped to classroom if enrolled).
     */
    public function ranking(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $studentClassrooms = $this->cache->getStudentClassrooms($user->id);
        $selectedClassroomId = $request->query('class_id')
            ? (int) $request->query('class_id')
            : ($studentClassrooms[0]['id'] ?? null);

        $classmates = $selectedClassroomId
            ? $this->cache->getClassroomRanking($selectedClassroomId, $user->id)
            : $this->cache->getStudentRanking($user->id);

        $selectedClass = $selectedClassroomId
            ? collect($studentClassrooms)->firstWhere('id', $selectedClassroomId)
            : null;

        return Inertia::render('students/Ranking', [
            'user' => $this->formatUserPayload($user),
            'classmates' => $classmates,
            'classrooms' => $studentClassrooms,
            'active_classroom' => $selectedClass,
        ]);
    }

    /**
     * Display the student audio and preferences settings page.
     */
    public function settings(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Settings', [
            'user' => $this->formatUserPayload($user),
        ]);
    }

    /**
     * Persist the authenticated student's preferred Pixar color world theme.
     */
    public function updateTheme(UpdateStudentThemeRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $validated = $request->safe()->only(['ui_theme']);

        $user->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'ui_theme' => $user->ui_theme?->value ?? PixarWorldTheme::Toybox->value,
            ]);
        }

        return back();
    }

    /**
     * Display the interactive 3D module expedition progress map for a student.
     */
    public function progress(Request $request, ?Module $module = null): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $resolvedModule = $module ?? Module::query()->reorder()->latest('created_at')->latest('id')->first();

        if (! $resolvedModule) {
            return Inertia::render('students/Progress', [
                'user' => $this->formatUserPayload($user),
            ]);
        }

        $mapData = $this->cache->getStudentModuleMapData($resolvedModule);
        $progress = $this->cache->getStudentModuleProgress(
            $user->id,
            $resolvedModule->id,
            $mapData['lecture_ids'],
            $mapData['quiz_ids'],
        );

        return Inertia::render('students/Progress', [
            'user' => $this->formatUserPayload($user),
            'completedQuizIds' => $progress['completedQuizIds'],
            'pendingQuizIds' => $progress['pendingQuizIds'],
            'completedLectureIds' => $progress['completedLectureIds'],
            'module' => $mapData['module'],
            'lectures' => $mapData['lectures'],
            'quizzes' => $mapData['quizzes'],
        ]);
    }

    /**
     * Display the interactive multi-format student quiz arena page.
     */
    public function showQuiz(Request $request, ?Module $module = null, ?Quiz $quiz = null): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $resolvedModule = $module ?? Module::query()->reorder()->latest('created_at')->latest('id')->first();

        if (! $resolvedModule) {
            return Inertia::render('students/views/quiz/Quiz', [
                'user' => $this->formatUserPayload($user),
            ]);
        }

        $arenaData = $this->cache->getStudentQuizArenaData($resolvedModule, $quiz);
        $resolvedQuizId = $arenaData['quiz']['id'] ?? null;

        $existingAttempt = is_int($resolvedQuizId)
            ? $this->cache->getStudentLatestQuizAttempt($user->id, $resolvedModule->id, $resolvedQuizId)
            : null;

        return Inertia::render('students/views/quiz/Quiz', [
            'user' => $this->formatUserPayload($user),
            'module' => $arenaData['module'],
            'quiz' => $arenaData['quiz'],
            'existingAttempt' => $existingAttempt,
            'siblingQuizzes' => $arenaData['siblingQuizzes'],
        ]);
    }

    /**
     * Display the full-screen 1:1 student lecture content reader matching the teacher's studio layout.
     */
    public function showLecture(Request $request, ?Module $module = null, ?Lecture $lecture = null): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $resolvedModule = $module ?? Module::query()->reorder()->latest('created_at')->latest('id')->first();

        if (! $resolvedModule) {
            return Inertia::render('students/views/modules/LectureContent', [
                'user' => $this->formatUserPayload($user),
            ]);
        }

        $readerData = $this->cache->getLectureReaderData($resolvedModule, $lecture);

        if ($readerData['lecture'] === null) {
            return Inertia::render('students/views/modules/LectureContent', [
                'user' => $this->formatUserPayload($user),
                'module' => $readerData['module'],
            ]);
        }

        return Inertia::render('students/views/modules/LectureContent', [
            'user' => $this->formatUserPayload($user),
            'module' => $readerData['module'],
            'lecture' => $readerData['lecture'],
            'contents' => $readerData['contents'],
            'siblingLectures' => $readerData['siblingLectures'],
        ]);
    }

    /**
     * Mark a lecture as completed by the student and award gamification XP.
     */
    public function completeLecture(Request $request, Module $module, Lecture $lecture): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['message' => 'Only students can mark lectures complete.'], 403);
        }

        $baseXp = (int) ($lecture->xp_reward ?? 100);
        $progression = $this->progressionService->recordLectureCompletion($user, $lecture, $baseXp);

        $this->cache->invalidateStudentModuleProgress($user->id, $module->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'progression' => $progression,
            ]);
        }

        return back();
    }
}
