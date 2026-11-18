<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Enums\PixarWorldTheme;
use App\Enums\UserRole;
use App\Http\Requests\EquipShopItemRequest;
use App\Http\Requests\JoinModuleRequest;
use App\Http\Requests\PurchaseShopItemRequest;
use App\Http\Requests\UpdateStudentThemeRequest;
use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\Lecture;
use App\Models\Module;
use App\Models\ModuleBonusClaim;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\ShopItem;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use App\Services\Gamification\LevelProgressionService;
use App\Services\Gamification\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Students only ever see the top of the leaderboard, so nobody is shown a low rank.
     */
    private const RANKING_VISIBLE_COUNT = 5;

    public function __construct(
        private readonly EModuleCacheService $cache,
        private readonly LevelProgressionService $progressionService,
        private readonly ShopService $shopService,
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
                'total_gems' => (int) ($profile->total_gems ?? 0),
                'gems_spent' => (int) $profile->gems_spent,
                'gem_balance' => $profile->gemBalance(),
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
     * The old dashboard URL now lands on the classrooms page, which is the student home.
     */
    public function index(Request $request): RedirectResponse
    {
        if (! $request->user()->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return redirect()->route('students.classes.index');
    }

    /**
     * Student home: every classroom the student belongs to, plus their module-code adventures and the join card.
     */
    public function classrooms(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Classrooms', [
            'user' => $this->formatUserPayload($user),
            'classrooms' => $this->cache->getStudentClassrooms($user->id),
            'direct_modules_count' => count($this->cache->getStudentDirectModules($user->id)),
        ]);
    }

    /**
     * Show only the published modules assigned to one classroom the student belongs to.
     */
    public function showClassroom(Request $request, Classroom $classroom): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $classroomCard = collect($this->cache->getStudentClassrooms($user->id))->firstWhere('id', $classroom->id);

        if ($classroomCard === null) {
            return redirect()->route('students.classes.index');
        }

        return Inertia::render('students/ClassroomModules', [
            'user' => $this->formatUserPayload($user),
            'classroom' => $classroomCard,
            'modules' => $this->withStudentProgress($this->cache->getClassroomStudentModules($classroom->id), $user->id),
            'joined_message' => $request->session()->get('success'),
        ]);
    }

    /**
     * Show the modules the student joined directly with a module code, outside any classroom.
     */
    public function codeAdventures(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/ClassroomModules', [
            'user' => $this->formatUserPayload($user),
            'classroom' => null,
            'modules' => $this->withStudentProgress($this->cache->getStudentDirectModules($user->id), $user->id),
            'joined_message' => $request->session()->get('success'),
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

            return redirect()
                ->route('students.classes.show', $classroom)
                ->with('success', 'Hooray! You joined classroom "'.$classroom->name.'" and unlocked your room reading adventures!');
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

        return redirect()
            ->route('students.classes.code-adventures')
            ->with('success', 'Hooray! You joined "'.$module->title.'"!');
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
        $memberClassroomIds = array_column($studentClassrooms, 'id');
        $requestedClassroomId = (int) $request->query('class_id');

        // Only rank classrooms the student actually belongs to; otherwise fall back to their first one.
        $selectedClassroomId = in_array($requestedClassroomId, $memberClassroomIds, true)
            ? $requestedClassroomId
            : ($memberClassroomIds[0] ?? null);

        $fullRanking = $selectedClassroomId
            ? $this->cache->getClassroomRanking($selectedClassroomId, $user->id)
            : $this->cache->getStudentRanking($user->id);

        $selectedClass = $selectedClassroomId
            ? collect($studentClassrooms)->firstWhere('id', $selectedClassroomId)
            : null;

        // Only students who have earned stars this week make the board, so it starts empty every Monday.
        $weeklyLeaders = collect($fullRanking)
            ->filter(static fn (array $student): bool => $student['weekly_stars'] > 0)
            ->take(self::RANKING_VISIBLE_COUNT)
            ->values();

        return Inertia::render('students/Ranking', [
            'user' => $this->formatUserPayload($user),
            'classmates' => $weeklyLeaders
                ->map(static fn (array $student): array => Arr::except($student, ['email']))
                ->all(),
            'classmatesCount' => count($fullRanking),
            'me' => $this->formatOwnRankingStanding($fullRanking, $weeklyLeaders->all()),
            'active_classroom' => $selectedClass,
            'week_resets_at' => now()->startOfWeek()->addWeek()->toIso8601String(),
        ]);
    }

    /**
     * The student's own weekly standing, without exposing an exact rank outside the visible top list.
     *
     * @param  list<array{rank: int, xp: int, stars: int, weekly_stars: int, level: int, is_current_user: bool}>  $fullRanking
     * @param  list<array{rank: int, weekly_stars: int}>  $weeklyLeaders
     * @return array{rank: int|null, in_top: bool, xp: int, stars: int, weekly_stars: int, level: int, stars_to_top: int}|null
     */
    private function formatOwnRankingStanding(array $fullRanking, array $weeklyLeaders): ?array
    {
        $own = collect($fullRanking)->firstWhere('is_current_user', true);

        if ($own === null) {
            return null;
        }

        $inTop = $own['weekly_stars'] > 0 && $own['rank'] <= self::RANKING_VISIBLE_COUNT;
        $boardIsFull = count($weeklyLeaders) >= self::RANKING_VISIBLE_COUNT;
        $lastVisible = $weeklyLeaders[count($weeklyLeaders) - 1] ?? null;

        $starsToTop = match (true) {
            $inTop => 0,
            ! $boardIsFull || $lastVisible === null => 1,
            default => max(1, $lastVisible['weekly_stars'] - $own['weekly_stars'] + 1),
        };

        return [
            'rank' => $inTop ? $own['rank'] : null,
            'in_top' => $inTop,
            'xp' => $own['xp'],
            'stars' => $own['stars'],
            'weekly_stars' => $own['weekly_stars'],
            'level' => $own['level'],
            'stars_to_top' => $starsToTop,
        ];
    }

    /**
     * Display the student classmates directory (strictly scoped to shared enrolled classrooms).
     */
    public function classmates(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        $studentClassrooms = $this->cache->getStudentClassrooms($user->id);
        $memberClassroomIds = array_column($studentClassrooms, 'id');

        if (empty($memberClassroomIds)) {
            return Inertia::render('students/Classmates', [
                'user' => $this->formatUserPayload($user),
                'classrooms' => [],
                'classmates' => [],
                'active_classroom_id' => null,
            ]);
        }

        $requestedClassroomId = (int) $request->query('class_id');
        $activeClassroomId = in_array($requestedClassroomId, $memberClassroomIds, true)
            ? $requestedClassroomId
            : null;

        $targetClassroomIds = $activeClassroomId ? [$activeClassroomId] : $memberClassroomIds;

        $avatars = ['fox', 'astro', 'knight', 'owl', 'bear', 'dragon'];
        $avatarBgs = [
            'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
            'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
            'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
            'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
        ];

        $classmatesQuery = User::query()
            ->where('users.role', UserRole::Student)
            ->whereHas('classrooms', static function ($q) use ($targetClassroomIds): void {
                $q->whereIn('classrooms.id', $targetClassroomIds);
            })
            ->with([
                'studentProfile.equippedAvatarItem',
                'classrooms' => static function ($q) use ($memberClassroomIds): void {
                    $q->whereIn('classrooms.id', $memberClassroomIds)
                        ->select('classrooms.id', 'classrooms.name', 'classrooms.theme', 'classrooms.icon', 'classrooms.grade_level');
                },
            ])
            ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
            ->orderByDesc(DB::raw('COALESCE(student_profiles.total_xp, 0)'))
            ->orderBy('users.name')
            ->select('users.*');

        $classmates = $classmatesQuery->get()->map(function (User $student) use ($user, $avatars, $avatarBgs): array {
            $profile = $student->studentProfile;
            $xp = (int) ($profile?->total_xp ?? 0);
            $stars = (int) ($profile?->total_stars ?? 0);
            $level = (int) ($profile?->level ?? 1);
            $gems = $profile ? $profile->gemBalance() : 0;
            $quizzesPassed = (int) ($profile?->quizzes_passed_count ?? 0);
            $lecturesCompleted = (int) ($profile?->lectures_completed_count ?? 0);

            $avatarIdx = abs($student->id) % count($avatars);
            $bgIdx = abs($student->id) % count($avatarBgs);

            return [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'is_current_user' => (bool) ($student->id === $user->id),
                'avatar' => $avatars[$avatarIdx],
                'avatarBg' => $avatarBgs[$bgIdx],
                'equipped_avatar' => $profile?->equippedAvatarItem?->metadata ?? null,
                'level' => $level,
                'level_title' => $this->progressionService->getLevelDetails($xp)['title'] ?? "Level {$level} Explorer",
                'xp' => $xp,
                'stars' => $stars,
                'gems' => $gems,
                'quizzes_passed' => $quizzesPassed,
                'lectures_completed' => $lecturesCompleted,
                'shared_classrooms' => $student->classrooms->map(static fn (Classroom $c): array => [
                    'id' => (int) $c->id,
                    'name' => (string) $c->name,
                    'theme' => (string) $c->theme,
                    'icon' => (string) $c->icon,
                    'grade_level' => (string) ($c->grade_level ?? ''),
                ])->values()->all(),
            ];
        })->values()->all();

        return Inertia::render('students/Classmates', [
            'user' => $this->formatUserPayload($user),
            'classrooms' => $studentClassrooms,
            'classmates' => $classmates,
            'active_classroom_id' => $activeClassroomId,
        ]);
    }

    /**
     * Display a single classmate's public gamer profile, strictly checking shared classroom membership.
     */
    public function showClassmateProfile(Request $request, User $classmate): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        if (! $classmate->isStudent()) {
            abort(404, 'Classmate not found.');
        }

        $studentClassrooms = $this->cache->getStudentClassrooms($user->id);
        $memberClassroomIds = array_column($studentClassrooms, 'id');

        $sharesClassroom = $user->id === $classmate->id || $classmate->classrooms()
            ->whereIn('classrooms.id', $memberClassroomIds)
            ->exists();

        if (! $sharesClassroom) {
            abort(403, 'You can only view profiles of classmates who share a classroom with you.');
        }

        $profile = $classmate->getOrCreateStudentProfile();
        $xp = (int) $profile->total_xp;
        $levelDetails = $this->progressionService->getLevelDetails($xp);

        $avatars = ['fox', 'astro', 'knight', 'owl', 'bear', 'dragon'];
        $avatarBgs = [
            'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
            'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
            'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
            'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
        ];

        $avatarIdx = abs($classmate->id) % count($avatars);
        $bgIdx = abs($classmate->id) % count($avatarBgs);

        $sharedRooms = $classmate->classrooms()
            ->whereIn('classrooms.id', $memberClassroomIds)
            ->select('classrooms.id', 'classrooms.name', 'classrooms.theme', 'classrooms.icon', 'classrooms.grade_level')
            ->get()
            ->map(static fn (Classroom $c): array => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'theme' => (string) $c->theme,
                'icon' => (string) $c->icon,
                'grade_level' => (string) ($c->grade_level ?? ''),
            ])
            ->values()
            ->all();

        return Inertia::render('students/ClassmatesProfile', [
            'user' => $this->formatUserPayload($user),
            'classmate' => [
                'id' => (int) $classmate->id,
                'name' => (string) $classmate->name,
                'is_current_user' => (bool) ($classmate->id === $user->id),
                'avatar' => $avatars[$avatarIdx],
                'avatarBg' => $avatarBgs[$bgIdx],
                'equipped_avatar' => $profile->equippedAvatarItem?->metadata ?? null,
                'level' => (int) $levelDetails['level'],
                'level_title' => (string) $levelDetails['title'],
                'level_badge' => (string) $levelDetails['badge'],
                'xp' => $xp,
                'stars' => (int) $profile->total_stars,
                'gems' => $profile->gemBalance(),
                'quizzes_passed' => (int) $profile->quizzes_passed_count,
                'lectures_completed' => (int) $profile->lectures_completed_count,
                'progress_pct' => (int) $levelDetails['progress_pct'],
                'shared_classrooms' => $sharedRooms,
            ],
        ]);
    }

    /**
     * Display the student gem bazaar shop page.
     */
    public function shop(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('teachers.dashboard');
        }

        return Inertia::render('students/Shop', [
            'user' => $this->formatUserPayload($user),
            'shop' => $this->shopService->storefrontFor($user),
        ]);
    }

    /**
     * Spend gems on one unit of a shop item.
     */
    public function purchaseShopItem(PurchaseShopItemRequest $request, ShopItem $shopItem): JsonResponse|RedirectResponse
    {
        $result = $this->shopService->purchase($request->user(), $shopItem);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'purchase' => $result,
            ]);
        }

        return back();
    }

    /**
     * Equip an owned cosmetic shop item.
     */
    public function equipShopItem(EquipShopItemRequest $request, ShopItem $shopItem): JsonResponse|RedirectResponse
    {
        $this->shopService->equip($request->user(), $shopItem);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
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
            'bonusClaim' => $this->formatBonusClaim($user->id, $resolvedModule->id),
            'backLink' => $this->resolveModuleBackLink($user, $resolvedModule, $request->query('from')),
        ]);
    }

    /**
     * Work out where the map's back button should go: the classroom (or Code Adventures list)
     * the student opened this module from, verified against their real memberships.
     *
     * @return array{url: string, label: string}
     */
    private function resolveModuleBackLink(User $user, Module $module, mixed $from): array
    {
        $classroomCards = collect($this->cache->getStudentClassrooms($user->id))->keyBy('id');

        $moduleClassroomIds = $classroomCards->isEmpty()
            ? collect()
            : ClassroomModule::query()
                ->where('module_id', $module->id)
                ->whereIn('classroom_id', $classroomCards->keys())
                ->pluck('classroom_id')
                ->map(static fn ($id): int => (int) $id);

        $hasCodeEnrollment = fn (): bool => ModuleEnrollment::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->exists();

        $classroomLink = fn (int $classroomId): array => [
            'url' => route('students.classes.show', $classroomId),
            'label' => (string) $classroomCards->get($classroomId)['name'],
        ];

        $codeLink = [
            'url' => route('students.classes.code-adventures'),
            'label' => 'Code Adventures',
        ];

        if (is_string($from) && ctype_digit($from) && $moduleClassroomIds->contains((int) $from)) {
            return $classroomLink((int) $from);
        }

        if ($from === 'code' && $hasCodeEnrollment()) {
            return $codeLink;
        }

        if ($moduleClassroomIds->isNotEmpty()) {
            return $classroomLink($moduleClassroomIds->first());
        }

        if ($hasCodeEnrollment()) {
            return $codeLink;
        }

        return [
            'url' => route('students.classes.index'),
            'label' => 'All Classrooms',
        ];
    }

    /**
     * Claim a module's one-time Treasure Vault bonus XP once every lecture and quiz is complete.
     */
    public function claimModuleBonus(Request $request, Module $module): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['message' => 'Only students can claim treasure vault bonuses.'], 403);
        }

        $mapData = $this->cache->getStudentModuleMapData($module);
        $progress = $this->cache->getStudentModuleProgress(
            $user->id,
            $module->id,
            $mapData['lecture_ids'],
            $mapData['quiz_ids'],
        );

        if (! $this->hasCompletedEveryQuest($mapData, $progress)) {
            $message = 'Finish every island quest in this module to unlock the treasure vault.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['bonus' => $message]);
        }

        $claim = $this->progressionService->claimModuleBonus($user, $module);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'claim' => $claim,
            ]);
        }

        return back();
    }

    /**
     * @param  array{lecture_ids: list<int>, quiz_ids: list<int>}  $mapData
     * @param  array{completedQuizIds: list<int>, completedLectureIds: list<int>}  $progress
     */
    private function hasCompletedEveryQuest(array $mapData, array $progress): bool
    {
        $lectureIds = $mapData['lecture_ids'];
        $quizIds = $mapData['quiz_ids'];

        if ($lectureIds === [] && $quizIds === []) {
            return false;
        }

        return array_diff($lectureIds, $progress['completedLectureIds']) === []
            && array_diff($quizIds, $progress['completedQuizIds']) === [];
    }

    /**
     * Stamp each module card with the student's real quest progress (matches the expedition map).
     *
     * @param  list<array<string, mixed>>  $modules
     * @return list<array<string, mixed>>
     */
    private function withStudentProgress(array $modules, int $userId): array
    {
        $completedCounts = $this->cache->getStudentCompletedCountsByModule(
            $userId,
            array_map(static fn (array $module): int => (int) $module['id'], $modules),
        );

        return array_map(static function (array $module) use ($completedCounts): array {
            $total = (int) ($module['lectures_count'] ?? 0) + (int) ($module['quizzes_count'] ?? 0);
            $completed = min($total, $completedCounts[(int) $module['id']] ?? 0);

            return [
                ...$module,
                'progress' => [
                    'completed' => $completed,
                    'total' => $total,
                    'percent' => $total > 0 ? (int) floor(($completed / $total) * 100) : 0,
                ],
            ];
        }, $modules);
    }

    /**
     * @return array{claimed: bool, xp_awarded: int|null, claimed_at: string|null}
     */
    private function formatBonusClaim(int $userId, int $moduleId): array
    {
        $claim = ModuleBonusClaim::query()
            ->where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->first(['xp_awarded', 'claimed_at']);

        return [
            'claimed' => $claim !== null,
            'xp_awarded' => $claim?->xp_awarded,
            'claimed_at' => $claim?->claimed_at?->toIso8601String(),
        ];
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
