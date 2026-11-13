<?php

namespace App\Services\Cache;

use App\Enums\ModuleStatus;
use App\Enums\QuizType;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\ColorConflictCard;
use App\Models\ConflictMapEvent;
use App\Models\ConflictMapZone;
use App\Models\EvaluationItem;
use App\Models\EvaluationItemOption;
use App\Models\EvaluationWordHelper;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\MatchingChoice;
use App\Models\MatchingItem;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizContext;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use App\Models\StudentLectureCompletion;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EModuleCacheService
{
    private const string KEY_PREFIX = 'v1';

    private const int EPOCH_TTL_SECONDS = 2592000; // 30 days

    private const string FLEXIBLE_CREATED_PREFIX = 'illuminate:cache:flexible:created:';

    /**
     * Retrieve published modules enrolled by a student with dynamic `is_new` freshness.
     *
     * @return list<array<string, mixed>>
     */
    public function getStudentModules(?int $studentId = null): array
    {
        $catEpoch = $this->getEpoch($this->catalogEpochKey());

        if ($studentId !== null) {
            $epoch = $this->getEpoch($this->studentEnrollmentEpochKey($studentId));
            $key = self::KEY_PREFIX.":student:{$studentId}:e{$epoch}:c{$catEpoch}:enrolled_modules";
        } else {
            $key = self::KEY_PREFIX.":student:all:c{$catEpoch}:modules";
        }

        /** @var list<array<string, mixed>> $modules */
        $modules = $this->rememberFlexible($key, 300, 900, function () use ($studentId): array {
            $query = Module::query()
                ->where('status', ModuleStatus::Published);

            if ($studentId !== null) {
                $query->whereHas('enrollments', fn ($q) => $q->where('user_id', $studentId));
            }

            return $query
                ->reorder()
                ->latest('created_at')
                ->latest('id')
                ->withCount(['lectures', 'quizzes'])
                ->get([
                    'id',
                    'title',
                    'slug',
                    'join_code',
                    'grade_level',
                    'description',
                    'theme',
                    'icon',
                    'cover_image_path',
                    'xp_bonus',
                    'boss_name',
                    'status',
                    'created_at',
                ])
                ->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'join_code' => $module->join_code,
                    'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                    'description' => $module->description,
                    'theme' => $module->theme->value,
                    'icon' => $module->icon,
                    'cover_image_url' => $module->coverImageUrl(),
                    'xp_bonus' => $module->xp_bonus,
                    'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                    'lectures_count' => (int) $module->lectures_count,
                    'quizzes_count' => (int) $module->quizzes_count,
                    'status' => $module->status->value,
                    'status_label' => 'Unlocked',
                    'created_at_ts' => $module->created_at?->getTimestamp(),
                ])
                ->values()
                ->all();
        });

        $cutoff = now()->subDays(2)->getTimestamp();

        return array_map(static function (array $module) use ($cutoff): array {
            $createdAtTs = $module['created_at_ts'] ?? null;
            unset($module['created_at_ts']);
            $module['is_new'] = is_int($createdAtTs) && $createdAtTs >= $cutoff;

            return $module;
        }, $modules);
    }

    /**
     * Retrieve modules owned by a teacher with optional classroom filtering and dynamic `is_new` freshness.
     *
     * @return list<array<string, mixed>>
     */
    public function getTeacherModules(int $teacherId, ?int $classroomId = null): array
    {
        $catEpoch = $this->getEpoch($this->catalogEpochKey());
        $roomEpoch = $this->getEpoch($this->teacherClassroomsEpochKey($teacherId));
        $classSuffix = $classroomId !== null ? ":c{$classroomId}" : ':c0';
        $key = self::KEY_PREFIX.":teacher:{$teacherId}{$classSuffix}:e{$catEpoch}:r{$roomEpoch}:modules";

        /** @var list<array<string, mixed>> $modules */
        $modules = $this->rememberFlexible($key, 300, 900, function () use ($teacherId, $classroomId): array {
            $query = Module::query()
                ->where('user_id', $teacherId);

            if ($classroomId !== null) {
                $query->whereHas('classrooms', static function ($q) use ($classroomId): void {
                    $q->where('classrooms.id', $classroomId);
                });
            }

            return $query
                ->reorder()
                ->latest('created_at')
                ->latest('id')
                ->withCount('lectures')
                ->get([
                    'id',
                    'user_id',
                    'title',
                    'slug',
                    'grade_level',
                    'description',
                    'theme',
                    'icon',
                    'cover_image_path',
                    'xp_bonus',
                    'boss_name',
                    'max_students',
                    'join_code',
                    'status',
                    'created_at',
                ])
                ->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                    'description' => $module->description,
                    'theme' => $module->theme->value,
                    'icon' => $module->icon,
                    'cover_image_url' => $module->coverImageUrl(),
                    'xp_bonus' => $module->xp_bonus,
                    'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                    'max_students' => $module->max_students,
                    'join_code' => $module->join_code,
                    'lectures_count' => (int) $module->lectures_count,
                    'status' => $module->status->value,
                    'status_label' => match ($module->status) {
                        ModuleStatus::Published => 'Published',
                        ModuleStatus::Draft => 'Draft Module',
                        ModuleStatus::Archived => 'Archived',
                    },
                    'created_at' => $module->created_at?->toIso8601String(),
                    'created_at_ts' => $module->created_at?->getTimestamp(),
                ])
                ->values()
                ->all();
        });

        $cutoff = now()->subDays(2)->getTimestamp();

        return array_map(static function (array $module) use ($cutoff): array {
            $createdAtTs = $module['created_at_ts'] ?? null;
            unset($module['created_at_ts']);
            $module['is_new'] = is_int($createdAtTs) && $createdAtTs >= $cutoff;

            return $module;
        }, $modules);
    }

    /**
     * Retrieve lightweight module summaries owned by a teacher for grading filters.
     *
     * @return list<array{id: int, title: string, slug: string}>
     */
    public function getTeacherModuleSummaries(int $teacherId): array
    {
        $epoch = $this->getEpoch($this->catalogEpochKey());
        $key = self::KEY_PREFIX.":teacher:{$teacherId}:e{$epoch}:module_summaries";

        /** @var list<array{id: int, title: string, slug: string}> */
        return $this->rememberFlexible($key, 300, 900, function () use ($teacherId): array {
            return Module::query()
                ->where('user_id', $teacherId)
                ->reorder()
                ->latest('created_at')
                ->latest('id')
                ->get(['id', 'title', 'slug'])
                ->map(static fn (Module $module): array => [
                    'id' => (int) $module->id,
                    'title' => (string) $module->title,
                    'slug' => (string) $module->slug,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Retrieve the real student ranking leaderboard ordered by XP and Stars.
     *
     * @return list<array{
     *     id: int,
     *     rank: int,
     *     name: string,
     *     email: string,
     *     avatar: string,
     *     avatarBg: string,
     *     level: int,
     *     xp: int,
     *     stars: int,
     *     points: int,
     *     badge: string,
     *     quizzes_passed: int,
     *     lectures_completed: int,
     *     is_current_user: bool
     * }>
     */
    public function getStudentRanking(int $currentUserId): array
    {
        $epoch = $this->getEpoch($this->rankingEpochKey());
        $key = self::KEY_PREFIX.":students:e{$epoch}:ranking";

        /** @var list<array{id: int, rank: int, name: string, email: string, avatar: string, avatarBg: string, level: int, xp: int, stars: int, points: int, badge: string, quizzes_passed: int, lectures_completed: int}> $students */
        $students = $this->rememberFlexible($key, 180, 600, function (): array {
            $records = User::query()
                ->where('role', UserRole::Student)
                ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
                ->orderByDesc(DB::raw('COALESCE(student_profiles.total_xp, 0)'))
                ->orderByDesc(DB::raw('COALESCE(student_profiles.total_stars, 0)'))
                ->orderBy('users.name')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'student_profiles.total_xp',
                    'student_profiles.total_stars',
                    'student_profiles.level',
                    'student_profiles.quizzes_passed_count',
                    'student_profiles.lectures_completed_count',
                ])
                ->get();

            $avatars = ['fox', 'astro', 'knight', 'owl', 'bear', 'dragon'];
            $avatarBgs = [
                'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
                'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
                'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
                'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
            ];

            return $records->values()->map(function ($student, int $index) use ($avatars, $avatarBgs): array {
                $xp = (int) ($student->total_xp ?? 0);
                $stars = (int) ($student->total_stars ?? 0);
                $level = (int) ($student->level ?? 1);
                $rank = $index + 1;

                $badge = match ($rank) {
                    1 => '🥇 Champion',
                    2 => '🥈 Vanguard',
                    3 => '🥉 Explorer',
                    default => $rank <= 10 ? '⭐ Elite' : '🌟 Pioneer',
                };

                return [
                    'id' => (int) $student->id,
                    'rank' => $rank,
                    'name' => (string) $student->name,
                    'email' => (string) $student->email,
                    'avatar' => $avatars[$index % count($avatars)],
                    'avatarBg' => $avatarBgs[$index % count($avatarBgs)],
                    'level' => $level,
                    'xp' => $xp,
                    'stars' => $stars,
                    'points' => $xp,
                    'badge' => $badge,
                    'quizzes_passed' => (int) ($student->quizzes_passed_count ?? 0),
                    'lectures_completed' => (int) ($student->lectures_completed_count ?? 0),
                ];
            })->all();
        });

        return array_map(static fn (array $student): array => [
            ...$student,
            'is_current_user' => $student['id'] === $currentUserId,
        ], $students);
    }

    /**
     * Retrieve all classrooms belonging to a teacher with student & module counts.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     grade_level: string,
     *     class_code: string,
     *     theme: string,
     *     icon: string,
     *     description: ?string,
     *     max_students: int,
     *     is_active: bool,
     *     students_count: int,
     *     modules_count: int,
     *     created_at: ?string
     * }>
     */
    public function getTeacherClassrooms(int $teacherId): array
    {
        $epoch = $this->getEpoch($this->teacherClassroomsEpochKey($teacherId));
        $key = self::KEY_PREFIX.":teacher:{$teacherId}:e{$epoch}:classrooms";

        /** @var list<array{id: int, name: string, grade_level: string, class_code: string, theme: string, icon: string, description: ?string, max_students: int, is_active: bool, students_count: int, modules_count: int, created_at: ?string}> */
        return $this->rememberFlexible($key, 300, 900, static function () use ($teacherId): array {
            return Classroom::query()
                ->where('user_id', $teacherId)
                ->withCount(['students', 'modules'])
                ->orderBy('name')
                ->get()
                ->map(static fn (Classroom $c): array => [
                    'id' => (int) $c->id,
                    'name' => (string) $c->name,
                    'grade_level' => (string) ($c->grade_level ?? 'All Grades • Reading Legends'),
                    'class_code' => (string) $c->class_code,
                    'theme' => (string) $c->theme,
                    'icon' => (string) $c->icon,
                    'description' => $c->description,
                    'max_students' => (int) $c->max_students,
                    'is_active' => (bool) $c->is_active,
                    'students_count' => (int) $c->students_count,
                    'modules_count' => (int) $c->modules_count,
                    'created_at' => $c->created_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Retrieve students belonging to a teacher's classrooms (strictly tenant-isolated).
     *
     * @return list<array{
     *     id: int,
     *     rank: int,
     *     name: string,
     *     email: string,
     *     classroom_name: string,
     *     classroom_grade: string,
     *     avatar: string,
     *     avatarBg: string,
     *     level: int,
     *     xp: int,
     *     stars: int,
     *     points: int,
     *     badge: string,
     *     quizzes_passed: int,
     *     lectures_completed: int,
     *     joined_at: ?string
     * }>
     */
    public function getTeacherStudents(int $teacherId, ?int $classroomId = null): array
    {
        $teacherEpoch = $this->getEpoch($this->teacherClassroomsEpochKey($teacherId));
        $key = self::KEY_PREFIX.":teacher:{$teacherId}:c".($classroomId ?? 0).":e{$teacherEpoch}:students";

        /** @var list<array{id: int, rank: int, name: string, email: string, classroom_name: string, classroom_grade: string, avatar: string, avatarBg: string, level: int, xp: int, stars: int, points: int, badge: string, quizzes_passed: int, lectures_completed: int, joined_at: ?string}> */
        return $this->rememberFlexible($key, 120, 600, static function () use ($teacherId, $classroomId): array {
            $query = User::query()
                ->where('users.role', UserRole::Student)
                ->join('classroom_students', 'classroom_students.user_id', '=', 'users.id')
                ->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
                ->where('classrooms.user_id', $teacherId);

            if ($classroomId !== null) {
                $query->where('classrooms.id', $classroomId);
            }

            $records = $query
                ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
                ->orderByDesc(DB::raw('COALESCE(student_profiles.total_xp, 0)'))
                ->orderByDesc(DB::raw('COALESCE(student_profiles.total_stars, 0)'))
                ->orderBy('users.name')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'classrooms.name as classroom_name',
                    'classrooms.grade_level as classroom_grade',
                    'student_profiles.total_xp',
                    'student_profiles.total_stars',
                    'student_profiles.level',
                    'student_profiles.quizzes_passed_count',
                    'student_profiles.lectures_completed_count',
                    'classroom_students.joined_at',
                ])
                ->get();

            $avatars = ['fox', 'astro', 'knight', 'owl', 'bear', 'dragon'];
            $avatarBgs = [
                'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
                'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
                'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
                'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
            ];

            // Deduplicate if a student is in multiple classes of this teacher
            $grouped = [];
            foreach ($records as $rec) {
                if (! isset($grouped[$rec->id])) {
                    $grouped[$rec->id] = $rec;
                } else {
                    $grouped[$rec->id]->classroom_name .= ', '.$rec->classroom_name;
                }
            }

            return array_values(array_map(static function ($student, int $index) use ($avatars, $avatarBgs): array {
                $rank = $index + 1;
                $xp = (int) ($student->total_xp ?? 0);
                $stars = (int) ($student->total_stars ?? 0);
                $level = (int) ($student->level ?? 1);

                $badge = match ($rank) {
                    1 => '🥇 Champion',
                    2 => '🥈 Vanguard',
                    3 => '🥉 Explorer',
                    default => $rank <= 10 ? '⭐ Elite' : '🌟 Pioneer',
                };

                return [
                    'id' => (int) $student->id,
                    'rank' => $rank,
                    'name' => (string) $student->name,
                    'email' => (string) $student->email,
                    'classroom_name' => (string) $student->classroom_name,
                    'classroom_grade' => (string) ($student->classroom_grade ?? 'Reading Legends'),
                    'avatar' => $avatars[$index % count($avatars)],
                    'avatarBg' => $avatarBgs[$index % count($avatarBgs)],
                    'level' => $level,
                    'xp' => $xp,
                    'stars' => $stars,
                    'points' => $xp,
                    'badge' => $badge,
                    'quizzes_passed' => (int) ($student->quizzes_passed_count ?? 0),
                    'lectures_completed' => (int) ($student->lectures_completed_count ?? 0),
                    'joined_at' => $student->joined_at ? date('Y-m-d', strtotime($student->joined_at)) : null,
                ];
            }, array_values($grouped), array_keys(array_values($grouped))));
        });
    }

    /**
     * Retrieve the real student ranking strictly within a single classroom.
     *
     * @return list<array{
     *     id: int,
     *     rank: int,
     *     name: string,
     *     email: string,
     *     avatar: string,
     *     avatarBg: string,
     *     level: int,
     *     xp: int,
     *     stars: int,
     *     points: int,
     *     badge: string,
     *     quizzes_passed: int,
     *     lectures_completed: int,
     *     is_current_user: bool
     * }>
     */
    public function getClassroomRanking(int $classroomId, int $currentUserId = 0): array
    {
        $epoch = $this->getEpoch($this->classroomEpochKey($classroomId));
        $key = self::KEY_PREFIX.":classroom:{$classroomId}:e{$epoch}:ranking";

        /** @var list<array{id: int, rank: int, name: string, email: string, avatar: string, avatarBg: string, level: int, xp: int, stars: int, points: int, badge: string, quizzes_passed: int, lectures_completed: int}> $students */
        $students = $this->rememberFlexible($key, 180, 600, static function () use ($classroomId): array {
            $records = User::query()
                ->where('users.role', UserRole::Student)
                ->join('classroom_students', 'classroom_students.user_id', '=', 'users.id')
                ->where('classroom_students.classroom_id', $classroomId)
                ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
                ->orderByDesc(DB::raw('COALESCE(student_profiles.total_xp, 0)'))
                ->orderByDesc(DB::raw('COALESCE(student_profiles.total_stars, 0)'))
                ->orderBy('users.name')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'student_profiles.total_xp',
                    'student_profiles.total_stars',
                    'student_profiles.level',
                    'student_profiles.quizzes_passed_count',
                    'student_profiles.lectures_completed_count',
                ])
                ->get();

            $avatars = ['fox', 'astro', 'knight', 'owl', 'bear', 'dragon'];
            $avatarBgs = [
                'linear-gradient(145deg, #FFF7ED 0%, #FDBA74 100%)',
                'linear-gradient(145deg, #EFF6FF 0%, #93C5FD 100%)',
                'linear-gradient(145deg, #ECFDF5 0%, #6EE7B7 100%)',
                'linear-gradient(145deg, #F5F3FF 0%, #C4B5FD 100%)',
            ];

            return $records->values()->map(static function ($student, int $index) use ($avatars, $avatarBgs): array {
                $rank = $index + 1;
                $xp = (int) ($student->total_xp ?? 0);
                $stars = (int) ($student->total_stars ?? 0);
                $level = (int) ($student->level ?? 1);

                $badge = match ($rank) {
                    1 => '🥇 Champion',
                    2 => '🥈 Vanguard',
                    3 => '🥉 Explorer',
                    default => $rank <= 10 ? '⭐ Elite' : '🌟 Pioneer',
                };

                return [
                    'id' => (int) $student->id,
                    'rank' => $rank,
                    'name' => (string) $student->name,
                    'email' => (string) $student->email,
                    'avatar' => $avatars[$index % count($avatars)],
                    'avatarBg' => $avatarBgs[$index % count($avatarBgs)],
                    'level' => $level,
                    'xp' => $xp,
                    'stars' => $stars,
                    'points' => $xp,
                    'badge' => $badge,
                    'quizzes_passed' => (int) ($student->quizzes_passed_count ?? 0),
                    'lectures_completed' => (int) ($student->lectures_completed_count ?? 0),
                ];
            })->all();
        });

        return array_map(static fn (array $student): array => [
            ...$student,
            'is_current_user' => $student['id'] === $currentUserId,
        ], $students);
    }

    /**
     * Retrieve list of classrooms a student has joined.
     *
     * @return list<array{id: int, name: string, grade_level: string, theme: string, icon: string, teacher_name: string}>
     */
    public function getStudentClassrooms(int $studentId): array
    {
        return Classroom::query()
            ->join('classroom_students', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->where('classroom_students.user_id', $studentId)
            ->where('classrooms.is_active', true)
            ->with('teacher')
            ->select('classrooms.*')
            ->get()
            ->map(static fn (Classroom $c): array => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'grade_level' => (string) ($c->grade_level ?? 'Reading Legends'),
                'class_code' => (string) $c->class_code,
                'theme' => (string) $c->theme,
                'icon' => (string) $c->icon,
                'teacher_name' => (string) ($c->teacher?->name ?? 'Instructor'),
            ])
            ->values()
            ->all();
    }

    /**
     * Retrieve slim expedition map data (lectures + quiz summaries) for a module.
     *
     * @return array{
     *     module: array<string, mixed>,
     *     lectures: list<array<string, mixed>>,
     *     quizzes: list<array<string, mixed>>,
     *     lecture_ids: list<int>,
     *     quiz_ids: list<int>
     * }
     */
    public function getStudentModuleMapData(Module $module): array
    {
        $epoch = $this->getEpoch($this->moduleEpochKey($module->id));
        $key = self::KEY_PREFIX.":module:{$module->id}:me{$epoch}:student_map";

        /** @var array{module: array<string, mixed>, lectures: list<array<string, mixed>>, quizzes: list<array<string, mixed>>, lecture_ids: list<int>, quiz_ids: list<int>} */
        return $this->rememberFlexible($key, 300, 1200, function () use ($module): array {
            $module->load([
                'lectures' => static fn ($query) => $query->with([
                    'contents' => static fn ($q) => $q->orderBy('sort_order'),
                ])->orderBy('sort_order'),
                'quizzes' => static fn ($query) => $query->with(['contexts'])
                    ->withCount([
                        'questions',
                        'conflictCards',
                        'mapEvents',
                        'matchingItems',
                        'evaluationItems',
                        'reflectionPrompts',
                    ])
                    ->orderBy('sort_order'),
            ]);

            $lectures = $module->lectures->map(static fn (Lecture $lecture): array => [
                'id' => $lecture->id,
                'title' => $lecture->title,
                'slug' => $lecture->slug,
                'description' => $lecture->description,
                'body' => $lecture->body,
                'estimated_duration_minutes' => $lecture->estimated_duration_minutes,
                'xp_reward' => $lecture->xp_reward,
                'sort_order' => $lecture->sort_order,
                'contents_count' => $lecture->contents->count(),
                'contents' => $lecture->contents->map(static fn (LectureContent $content): array => [
                    'id' => $content->id,
                    'type' => $content->type->value,
                    'layout_style' => $content->layout_style ?? 'standard',
                    'title' => $content->title,
                    'content' => $content->content,
                    'media_url' => $content->mediaUrl(),
                    'narration_audio_url' => $content->narrationAudioUrl(),
                    'narration_audio_name' => $content->narration_audio_name,
                    'vocabulary_glosses' => $content->vocabulary_glosses ?? [],
                    'external_url' => $content->external_url,
                    'caption' => $content->caption,
                    'sort_order' => $content->sort_order,
                ])->values()->all(),
            ])->values()->all();

            $quizzes = $module->quizzes->map(static function (Quiz $quiz): array {
                $itemsCount = match ($quiz->type->value) {
                    'color_the_conflict' => (int) $quiz->conflict_cards_count,
                    'the_conflict_map' => (int) $quiz->map_events_count,
                    'matching_type' => (int) $quiz->matching_items_count,
                    'evaluation' => (int) $quiz->evaluation_items_count,
                    'reflection' => (int) $quiz->reflection_prompts_count,
                    default => (int) $quiz->questions_count,
                };

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'slug' => $quiz->slug,
                    'type' => $quiz->type->value,
                    'description' => $quiz->description,
                    'reading_passage' => $quiz->contexts->first()?->scenario ?? $quiz->reading_passage,
                    'contexts' => $quiz->contexts->map(static fn (QuizContext $ctx): array => [
                        'id' => $ctx->id,
                        'scenario' => $ctx->scenario,
                        'hint' => $ctx->hint,
                    ])->values()->all(),
                    'xp_reward' => $quiz->xp_reward,
                    'passing_percentage' => $quiz->passing_percentage,
                    'time_limit_seconds' => $quiz->time_limit_seconds,
                    'sort_order' => $quiz->sort_order,
                    'questions_count' => max(1, $itemsCount),
                ];
            })->values()->all();

            return [
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'description' => $module->description,
                    'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                    'theme' => $module->theme->value,
                    'icon' => $module->icon,
                    'cover_image_url' => $module->coverImageUrl(),
                    'xp_bonus' => $module->xp_bonus,
                    'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                    'status' => $module->status->value,
                ],
                'lectures' => $lectures,
                'quizzes' => $quizzes,
                'lecture_ids' => $module->lectures->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all(),
                'quiz_ids' => $module->quizzes->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all(),
            ];
        });
    }

    /**
     * Retrieve a student's completed/pending milestone IDs for a module.
     *
     * @param  list<int>  $lectureIds
     * @param  list<int>  $quizIds
     * @return array{completedQuizIds: list<int>, pendingQuizIds: list<int>, completedLectureIds: list<int>}
     */
    public function getStudentModuleProgress(int $userId, int $moduleId, array $lectureIds, array $quizIds): array
    {
        $modEpoch = $this->getEpoch($this->moduleEpochKey($moduleId));
        $userModEpoch = $this->getEpoch($this->studentModuleEpochKey($userId, $moduleId));
        $key = self::KEY_PREFIX.":student:{$userId}:m:{$moduleId}:me{$modEpoch}:ue{$userModEpoch}:progress";

        /** @var array{completedQuizIds: list<int>, pendingQuizIds: list<int>, completedLectureIds: list<int>} */
        return $this->rememberSingleflight($key, 120, static function () use ($userId, $lectureIds, $quizIds): array {
            $completedQuizIds = [];
            $pendingQuizIds = [];

            if ($quizIds !== []) {
                $completedAttempts = QuizAttempt::query()
                    ->where('user_id', $userId)
                    ->whereIn('quiz_id', $quizIds)
                    ->whereIn('status', ['completed', 'pending_review'])
                    ->get(['quiz_id', 'status']);

                $completedQuizIds = $completedAttempts
                    ->pluck('quiz_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $pendingQuizIds = $completedAttempts
                    ->where('status', 'pending_review')
                    ->pluck('quiz_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            }

            $completedLectureIds = $lectureIds === []
                ? []
                : StudentLectureCompletion::query()
                    ->where('user_id', $userId)
                    ->whereIn('lecture_id', $lectureIds)
                    ->pluck('lecture_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

            return [
                'completedQuizIds' => $completedQuizIds,
                'pendingQuizIds' => $pendingQuizIds,
                'completedLectureIds' => $completedLectureIds,
            ];
        });
    }

    /**
     * Retrieve lecture reader payload for a module and optional target lecture.
     *
     * @return array{
     *     module: array<string, mixed>,
     *     lecture: array<string, mixed>|null,
     *     contents: list<array<string, mixed>>,
     *     siblingLectures: list<array<string, mixed>>
     * }
     */
    public function getLectureReaderData(Module $module, ?Lecture $lecture = null): array
    {
        $modEpoch = $this->getEpoch($this->moduleEpochKey($module->id));
        $targetKey = $lecture && $lecture->module_id === $module->id ? (string) $lecture->id : 'first';
        $key = self::KEY_PREFIX.":module:{$module->id}:lec:{$targetKey}:me{$modEpoch}:reader";

        /** @var array{module: array<string, mixed>, lecture: array<string, mixed>|null, contents: list<array<string, mixed>>, siblingLectures: list<array<string, mixed>>} */
        return $this->rememberFlexible($key, 600, 1800, function () use ($module, $lecture): array {
            $siblingLectures = $module->lectures()
                ->orderBy('sort_order')
                ->get(['id', 'module_id', 'title', 'slug', 'description', 'body', 'estimated_duration_minutes', 'xp_reward', 'sort_order']);

            $resolvedLecture = $lecture && $lecture->module_id === $module->id
                ? $siblingLectures->firstWhere('id', $lecture->id)
                : $siblingLectures->first();

            $modulePayload = [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'description' => $module->description,
                'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                'theme' => $module->theme->value,
                'icon' => $module->icon,
                'xp_bonus' => $module->xp_bonus,
                'boss_name' => $module->boss_name ?? 'Chapter Guardian',
            ];

            if (! $resolvedLecture) {
                return [
                    'module' => $modulePayload,
                    'lecture' => null,
                    'contents' => [],
                    'siblingLectures' => [],
                ];
            }

            $resolvedLecture->load(['contents' => static fn ($q) => $q->orderBy('sort_order')]);

            return [
                'module' => $modulePayload,
                'lecture' => [
                    'id' => $resolvedLecture->id,
                    'title' => $resolvedLecture->title,
                    'slug' => $resolvedLecture->slug,
                    'description' => $resolvedLecture->description,
                    'body' => $resolvedLecture->body,
                    'estimated_duration_minutes' => $resolvedLecture->estimated_duration_minutes,
                    'xp_reward' => $resolvedLecture->xp_reward,
                    'sort_order' => $resolvedLecture->sort_order,
                    'contents_count' => $resolvedLecture->contents->count(),
                ],
                'contents' => $resolvedLecture->contents->map(static fn (LectureContent $content): array => [
                    'id' => $content->id,
                    'type' => $content->type->value,
                    'layout_style' => $content->layout_style ?? 'standard',
                    'title' => $content->title,
                    'content' => $content->content,
                    'media_url' => $content->mediaUrl(),
                    'narration_audio_url' => $content->narrationAudioUrl(),
                    'narration_audio_name' => $content->narration_audio_name,
                    'vocabulary_glosses' => $content->vocabulary_glosses ?? [],
                    'external_url' => $content->external_url,
                    'duration_seconds' => $content->duration_seconds,
                    'caption' => $content->caption,
                    'sort_order' => $content->sort_order,
                ])->values()->all(),
                'siblingLectures' => $siblingLectures->map(static fn (Lecture $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'xp_reward' => $item->xp_reward,
                    'sort_order' => $item->sort_order,
                ])->values()->all(),
            ];
        });
    }

    /**
     * Retrieve single-quiz arena payload (loads only the relations required by the active quiz format).
     *
     * @return array{
     *     module: array<string, mixed>,
     *     quiz: array<string, mixed>|null,
     *     siblingQuizzes: list<array<string, mixed>>
     * }
     */
    public function getStudentQuizArenaData(Module $module, ?Quiz $quiz = null): array
    {
        $modEpoch = $this->getEpoch($this->moduleEpochKey($module->id));
        $targetKey = $quiz && $quiz->module_id === $module->id ? (string) $quiz->id : 'first';
        $key = self::KEY_PREFIX.":module:{$module->id}:quiz:{$targetKey}:me{$modEpoch}:arena";

        /** @var array{module: array<string, mixed>, quiz: array<string, mixed>|null, siblingQuizzes: list<array<string, mixed>>} */
        return $this->rememberFlexible($key, 600, 1800, function () use ($module, $quiz): array {
            $siblingQuizzes = $module->quizzes()
                ->orderBy('sort_order')
                ->get(['id', 'module_id', 'title', 'slug', 'type', 'description', 'reading_passage', 'xp_reward', 'passing_percentage', 'time_limit_seconds', 'sort_order']);

            $resolvedQuiz = $quiz && $quiz->module_id === $module->id
                ? $siblingQuizzes->firstWhere('id', $quiz->id)
                : $siblingQuizzes->first();

            if ($resolvedQuiz) {
                $this->loadRelationsForQuizType($resolvedQuiz);
            }

            return [
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'description' => $module->description,
                    'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                    'theme' => $module->theme->value,
                    'icon' => $module->icon,
                    'xp_bonus' => $module->xp_bonus,
                    'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                ],
                'quiz' => $resolvedQuiz ? $this->transformQuizForStudent($resolvedQuiz) : null,
                'siblingQuizzes' => $siblingQuizzes->map(static fn (Quiz $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'type' => $item->type->value,
                    'xp_reward' => $item->xp_reward,
                    'sort_order' => $item->sort_order,
                ])->values()->all(),
            ];
        });
    }

    /**
     * Retrieve a student's latest completed or pending attempt for a quiz.
     *
     * @return array<string, mixed>|null
     */
    public function getStudentLatestQuizAttempt(int $userId, int $moduleId, int $quizId): ?array
    {
        $userModEpoch = $this->getEpoch($this->studentModuleEpochKey($userId, $moduleId));
        $key = self::KEY_PREFIX.":student:{$userId}:m:{$moduleId}:q:{$quizId}:ue{$userModEpoch}:attempt";

        /** @var array<string, mixed>|null */
        return $this->rememberSingleflight($key, 120, static function () use ($userId, $quizId): ?array {
            $latestAttempt = QuizAttempt::query()
                ->where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->whereIn('status', ['completed', 'pending_review'])
                ->latest('id')
                ->first();

            if (! $latestAttempt) {
                return null;
            }

            $isPending = $latestAttempt->status === 'pending_review';

            return [
                'id' => $latestAttempt->id,
                'attempt_number' => $latestAttempt->attempt_number,
                'status' => $latestAttempt->status,
                'is_pending_review' => $isPending,
                'score' => $latestAttempt->score,
                'max_score' => $latestAttempt->max_score,
                'percentage' => (float) $latestAttempt->percentage,
                'stars' => $latestAttempt->stars,
                'is_passed' => $latestAttempt->is_passed,
                'xp_earned' => $latestAttempt->xp_earned,
                'time_spent_seconds' => $latestAttempt->time_spent_seconds,
                'completed_at' => $latestAttempt->completed_at?->format('M d, Y h:i A'),
                'teacher_feedback' => $latestAttempt->teacher_feedback,
            ];
        });
    }

    /**
     * Retrieve teacher module journey map payload (lean quiz projections + full lectures).
     *
     * @return array{
     *     module: array<string, mixed>,
     *     lectures: list<array<string, mixed>>,
     *     quizzes: list<array<string, mixed>>
     * }
     */
    public function getTeacherModuleMapData(Module $module): array
    {
        $epoch = $this->getEpoch($this->moduleEpochKey($module->id));
        $key = self::KEY_PREFIX.":module:{$module->id}:me{$epoch}:teacher_map";

        /** @var array{module: array<string, mixed>, lectures: list<array<string, mixed>>, quizzes: list<array<string, mixed>>} */
        return $this->rememberFlexible($key, 300, 1200, function () use ($module): array {
            $module->load([
                'lectures' => static fn ($query) => $query->with([
                    'contents' => static fn ($q) => $q->orderBy('sort_order'),
                ])->orderBy('sort_order'),
                'quizzes' => static fn ($query) => $query->with(['contexts'])
                    ->withCount([
                        'questions',
                        'conflictCards',
                        'mapEvents',
                        'matchingItems',
                        'evaluationItems',
                        'reflectionPrompts',
                    ])
                    ->orderBy('sort_order'),
            ]);

            return [
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'description' => $module->description,
                    'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                    'theme' => $module->theme->value,
                    'icon' => $module->icon,
                    'cover_image_url' => $module->coverImageUrl(),
                    'xp_bonus' => $module->xp_bonus,
                    'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                    'max_students' => $module->max_students,
                    'join_code' => $module->join_code,
                    'status' => $module->status->value,
                ],
                'lectures' => $module->lectures->map(static fn (Lecture $lecture): array => [
                    'id' => $lecture->id,
                    'title' => $lecture->title,
                    'slug' => $lecture->slug,
                    'description' => $lecture->description,
                    'body' => $lecture->body,
                    'estimated_duration_minutes' => $lecture->estimated_duration_minutes,
                    'xp_reward' => $lecture->xp_reward,
                    'sort_order' => $lecture->sort_order,
                    'is_published' => $lecture->is_published,
                    'contents_count' => $lecture->contents->count(),
                    'contents' => $lecture->contents->map(static fn (LectureContent $content): array => [
                        'id' => $content->id,
                        'type' => $content->type->value,
                        'layout_style' => $content->layout_style ?? 'standard',
                        'title' => $content->title,
                        'content' => $content->content,
                        'media_url' => $content->mediaUrl(),
                        'narration_audio_url' => $content->narrationAudioUrl(),
                        'narration_audio_name' => $content->narration_audio_name,
                        'vocabulary_glosses' => $content->vocabulary_glosses ?? [],
                        'external_url' => $content->external_url,
                        'caption' => $content->caption,
                        'sort_order' => $content->sort_order,
                    ])->values()->all(),
                ])->values()->all(),
                'quizzes' => $module->quizzes->map(static function (Quiz $quiz): array {
                    $firstContext = $quiz->contexts->first();
                    $itemsCount = match ($quiz->type->value) {
                        'color_the_conflict' => (int) $quiz->conflict_cards_count,
                        'the_conflict_map' => (int) $quiz->map_events_count,
                        'matching_type' => (int) $quiz->matching_items_count,
                        'evaluation' => (int) $quiz->evaluation_items_count,
                        'reflection' => (int) $quiz->reflection_prompts_count,
                        default => (int) $quiz->questions_count,
                    };

                    return [
                        'id' => $quiz->id,
                        'title' => $quiz->title,
                        'slug' => $quiz->slug,
                        'type' => $quiz->type->value,
                        'description' => $quiz->description,
                        'reading_passage' => $firstContext?->scenario ?? $quiz->reading_passage,
                        'context' => $firstContext ? [
                            'id' => $firstContext->id,
                            'scenario' => $firstContext->scenario,
                            'hint' => $firstContext->hint,
                        ] : null,
                        'contexts' => $quiz->contexts->map(static fn (QuizContext $ctx): array => [
                            'id' => $ctx->id,
                            'scenario' => $ctx->scenario,
                            'hint' => $ctx->hint,
                        ])->values()->all(),
                        'xp_reward' => $quiz->xp_reward,
                        'passing_percentage' => $quiz->passing_percentage,
                        'time_limit_seconds' => $quiz->time_limit_seconds,
                        'sort_order' => $quiz->sort_order,
                        'is_published' => $quiz->is_published,
                        'questions_count' => max(0, $itemsCount),
                    ];
                })->values()->all(),
            ];
        });
    }

    /**
     * Retrieve teacher quiz studio data for all quizzes in a module.
     *
     * @return array{module: array<string, mixed>, existingQuizzes: list<array<string, mixed>>}
     */
    public function getTeacherQuizStudioData(Module $module): array
    {
        $epoch = $this->getEpoch($this->moduleEpochKey($module->id));
        $key = self::KEY_PREFIX.":module:{$module->id}:me{$epoch}:studio";

        /** @var array{module: array<string, mixed>, existingQuizzes: list<array<string, mixed>>} */
        return $this->rememberSingleflight($key, 300, static function () use ($module): array {
            $nextOrder = $module->nextMilestoneSortOrder();

            $module->load([
                'quizzes' => static fn ($query) => $query->with([
                    'contexts',
                    'questions.options',
                    'conflictCards',
                    'mapZones',
                    'mapEvents',
                    'matchingChoices',
                    'matchingItems.correctChoice',
                    'evaluationWordHelpers',
                    'evaluationItems.options',
                    'reflectionPrompts',
                ])->orderBy('sort_order'),
            ]);

            return [
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'icon' => $module->icon,
                    'theme' => $module->theme->value,
                    'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                    'next_sort_order' => $nextOrder,
                ],
                'existingQuizzes' => $module->quizzes->map(static fn (Quiz $quiz): array => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'slug' => $quiz->slug,
                    'type' => $quiz->type->value,
                    'description' => $quiz->description,
                    'xp_reward' => $quiz->xp_reward,
                    'passing_percentage' => $quiz->passing_percentage,
                    'time_limit_seconds' => $quiz->time_limit_seconds,
                    'sort_order' => $quiz->sort_order,
                    'is_published' => $quiz->is_published,
                    'contexts' => $quiz->contexts->map(static fn (QuizContext $ctx): array => [
                        'scenario' => $ctx->scenario,
                        'hint' => $ctx->hint ?? '',
                    ])->values()->all(),
                    'questions' => $quiz->questions->map(static fn (QuizQuestion $q): array => [
                        'question_text' => $q->question_text,
                        'selection_type' => $q->selection_type->value,
                        'passage_context' => $q->passage_context ?? '',
                        'hint' => $q->hint ?? '',
                        'explanation' => $q->explanation ?? '',
                        'points' => $q->points,
                        'options' => $q->options->map(static fn (QuizQuestionOption $opt): array => [
                            'option_text' => $opt->option_text,
                            'is_correct' => $opt->is_correct,
                            'feedback' => $opt->feedback ?? '',
                        ])->values()->all(),
                    ])->values()->all(),
                    'cards' => $quiz->conflictCards->map(static fn (ColorConflictCard $c): array => [
                        'excerpt' => $c->excerpt,
                        'conflict_type' => $c->conflict_type->value,
                        'hint' => $c->hint ?? '',
                        'explanation' => $c->explanation ?? '',
                        'feedback_incorrect' => $c->feedback_incorrect ?? '',
                        'points' => $c->points,
                        'sort_order' => $c->sort_order,
                    ])->values()->all(),
                    'zones' => $quiz->mapZones->map(static fn (ConflictMapZone $z): array => [
                        'conflict_type' => $z->conflict_type->value,
                        'label' => $z->label,
                        'subtitle' => $z->subtitle ?? '',
                        'color_theme' => $z->color_theme ?? 'blue',
                        'sort_order' => $z->sort_order,
                    ])->values()->all(),
                    'events' => $quiz->mapEvents->map(static fn (ConflictMapEvent $e): array => [
                        'card_letter' => $e->card_letter,
                        'event_text' => $e->event_text,
                        'target_conflict_type' => $e->target_conflict_type->value,
                        'hint' => $e->hint ?? '',
                        'explanation' => $e->explanation ?? '',
                        'points' => $e->points,
                        'sort_order' => $e->sort_order,
                    ])->values()->all(),
                    'choices' => $quiz->matchingChoices->map(static fn (MatchingChoice $mc): array => [
                        'choice_letter' => $mc->choice_letter,
                        'choice_text' => $mc->choice_text,
                    ])->values()->all(),
                    'matching_items' => $quiz->matchingItems->map(static fn (MatchingItem $mi): array => [
                        'item_number' => $mi->item_number,
                        'description' => $mi->description,
                        'correct_choice_letter' => $mi->correctChoice?->choice_letter ?? 'A',
                        'hint' => $mi->hint ?? '',
                        'explanation' => $mi->explanation ?? '',
                        'points' => $mi->points,
                        'sort_order' => $mi->sort_order,
                    ])->values()->all(),
                    'word_helpers' => $quiz->evaluationWordHelpers->map(static fn (EvaluationWordHelper $wh): string => $wh->word)->values()->all(),
                    'evaluation_items' => $quiz->evaluationItems->map(static fn (EvaluationItem $ei): array => [
                        'section_title' => $ei->section_title,
                        'conflict_type' => $ei->conflict_type ?? 'character_vs_character',
                        'question_prompt' => $ei->question_prompt,
                        'step1_prompt' => $ei->step1_prompt ?? 'Step 1: Pick one option:',
                        'sentence_template' => $ei->sentence_template,
                        'hint' => $ei->hint ?? '',
                        'points' => $ei->points,
                        'sort_order' => $ei->sort_order,
                        'options' => $ei->options->map(static fn (EvaluationItemOption $eo): array => [
                            'option_text' => $eo->option_text,
                            'stance_value' => $eo->stance_value ?? '',
                            'is_correct' => $eo->is_correct,
                        ])->values()->all(),
                    ])->values()->all(),
                    'prompts' => $quiz->reflectionPrompts->map(static fn (ReflectionPrompt $rp): array => [
                        'prompt_text' => $rp->prompt_text,
                        'max_words' => $rp->max_words,
                        'min_words' => $rp->min_words,
                        'guiding_hint' => $rp->guiding_hint ?? '',
                        'starter_sentence' => $rp->starter_sentence ?? '',
                        'points' => $rp->points,
                        'sort_order' => $rp->sort_order,
                    ])->values()->all(),
                ])->values()->all(),
            ];
        });
    }

    /**
     * Retrieve total pending review count across a teacher's modules via single indexed JOIN.
     */
    public function getTeacherPendingGradingCount(int $teacherId): int
    {
        $epoch = $this->getEpoch($this->teacherGradingEpochKey($teacherId));
        $key = self::KEY_PREFIX.":teacher:{$teacherId}:ge{$epoch}:pending_count";

        return (int) $this->rememberSingleflight($key, 120, static function () use ($teacherId): int {
            return (int) DB::table('quiz_attempts')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->join('modules', 'modules.id', '=', 'quizzes.module_id')
                ->where('modules.user_id', $teacherId)
                ->where('quiz_attempts.status', 'pending_review')
                ->count();
        });
    }

    /**
     * Retrieve teacher grading overview stats via a single SQL conditional aggregate.
     *
     * @return array{pending_count: int, graded_count: int, total_count: int}
     */
    public function getTeacherGradingStats(int $teacherId): array
    {
        $epoch = $this->getEpoch($this->teacherGradingEpochKey($teacherId));
        $key = self::KEY_PREFIX.":teacher:{$teacherId}:ge{$epoch}:grading_stats";

        /** @var array{pending_count: int, graded_count: int, total_count: int} */
        return $this->rememberSingleflight($key, 120, static function () use ($teacherId): array {
            $row = DB::table('quiz_attempts')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->join('modules', 'modules.id', '=', 'quizzes.module_id')
                ->where('modules.user_id', $teacherId)
                ->selectRaw("
                    COUNT(*) as total_count,
                    COALESCE(SUM(CASE WHEN quiz_attempts.status = 'pending_review' THEN 1 ELSE 0 END), 0) as pending_count,
                    COALESCE(SUM(CASE WHEN quiz_attempts.status = 'completed' THEN 1 ELSE 0 END), 0) as graded_count
                ")
                ->first();

            return [
                'pending_count' => (int) ($row->pending_count ?? 0),
                'graded_count' => (int) ($row->graded_count ?? 0),
                'total_count' => (int) ($row->total_count ?? 0),
            ];
        });
    }

    /**
     * Retrieve aggregated quiz analytics directly from SQL instead of loading all attempt rows for stats.
     *
     * @return array{
     *     total_attempts: int,
     *     unique_students: int,
     *     pending_review_count: int,
     *     graded_count: int,
     *     passed_count: int,
     *     average_score: float|int,
     *     average_percentage: float|int,
     *     pass_rate: float|int,
     *     highest_score: int,
     *     lowest_score: int
     * }
     */
    public function getTeacherQuizAnalytics(int $quizId): array
    {
        $epoch = $this->getEpoch($this->quizAnalyticsEpochKey($quizId));
        $key = self::KEY_PREFIX.":quiz:{$quizId}:ae{$epoch}:analytics";

        /** @var array{total_attempts: int, unique_students: int, pending_review_count: int, graded_count: int, passed_count: int, average_score: float|int, average_percentage: float|int, pass_rate: float|int, highest_score: int, lowest_score: int} */
        return $this->rememberSingleflight($key, 120, static function () use ($quizId): array {
            $row = DB::table('quiz_attempts')
                ->where('quiz_id', $quizId)
                ->selectRaw("
                    COUNT(*) as total_attempts,
                    COUNT(DISTINCT user_id) as unique_students,
                    COALESCE(SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END), 0) as pending_review_count,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as graded_count,
                    COALESCE(SUM(CASE WHEN status = 'completed' AND is_passed = 1 THEN 1 ELSE 0 END), 0) as passed_count,
                    AVG(CASE WHEN status = 'completed' THEN score END) as avg_score,
                    AVG(CASE WHEN status = 'completed' THEN percentage END) as avg_pct,
                    MAX(CASE WHEN status = 'completed' THEN score END) as highest_score,
                    MIN(CASE WHEN status = 'completed' THEN score END) as lowest_score
                ")
                ->first();

            $completedCount = (int) ($row->graded_count ?? 0);
            $passedCount = (int) ($row->passed_count ?? 0);

            return [
                'total_attempts' => (int) ($row->total_attempts ?? 0),
                'unique_students' => (int) ($row->unique_students ?? 0),
                'pending_review_count' => (int) ($row->pending_review_count ?? 0),
                'graded_count' => $completedCount,
                'passed_count' => $passedCount,
                'average_score' => $completedCount > 0 ? round((float) ($row->avg_score ?? 0), 1) : 0,
                'average_percentage' => $completedCount > 0 ? round((float) ($row->avg_pct ?? 0), 1) : 0,
                'pass_rate' => $completedCount > 0 ? round(($passedCount / $completedCount) * 100, 1) : 0,
                'highest_score' => $completedCount > 0 ? (int) ($row->highest_score ?? 0) : 0,
                'lowest_score' => $completedCount > 0 ? (int) ($row->lowest_score ?? 0) : 0,
            ];
        });
    }

    /**
     * Load only the relations required for a quiz's specific format.
     */
    public function loadRelationsForQuizType(Quiz $quiz): Quiz
    {
        $relations = match ($quiz->type) {
            QuizType::MultipleChoice => ['contexts', 'questions.options'],
            QuizType::ColorTheConflict => ['contexts', 'conflictCards'],
            QuizType::TheConflictMap => ['contexts', 'mapZones', 'mapEvents'],
            QuizType::MatchingType => ['contexts', 'matchingChoices', 'matchingItems.correctChoice'],
            QuizType::Evaluation => ['contexts', 'evaluationWordHelpers', 'evaluationItems.options'],
            QuizType::Reflection => ['contexts', 'reflectionPrompts'],
        };

        return $quiz->loadMissing($relations);
    }

    /**
     * Bump the catalog epoch (invalidating student & teacher module dashboards).
     */
    public function invalidateCatalog(): void
    {
        $this->bumpEpoch($this->catalogEpochKey());
    }

    /**
     * Bump module epoch (and optionally catalog epoch when milestone counts change).
     */
    public function invalidateModule(int $moduleId, bool $bumpCatalog = false): void
    {
        $this->bumpEpoch($this->moduleEpochKey($moduleId));

        if ($bumpCatalog) {
            $this->invalidateCatalog();
        }
    }

    /**
     * Bump student progress epoch for a module.
     */
    public function invalidateStudentModuleProgress(int $userId, int $moduleId): void
    {
        $this->bumpEpoch($this->studentModuleEpochKey($userId, $moduleId));
    }

    /**
     * Bump student module progress, teacher grading counts/stats, and quiz analytics after submit or grade.
     */
    public function invalidateAttemptProgress(int $userId, int $moduleId, int $teacherId, int $quizId): void
    {
        $this->bumpEpoch($this->studentModuleEpochKey($userId, $moduleId));
        $this->bumpEpoch($this->teacherGradingEpochKey($teacherId));
        $this->bumpEpoch($this->quizAnalyticsEpochKey($quizId));
    }

    /**
     * Bump the student module enrollments epoch.
     */
    public function invalidateStudentEnrollment(int $userId): void
    {
        $this->bumpEpoch($this->studentEnrollmentEpochKey($userId));
    }

    /**
     * Bump the student ranking leaderboard epoch.
     */
    public function invalidateRanking(): void
    {
        $this->bumpEpoch($this->rankingEpochKey());
    }

    /**
     * Transform a Quiz model into the student arena payload.
     *
     * @return array<string, mixed>
     */
    private function transformQuizForStudent(Quiz $quiz): array
    {
        $contexts = $quiz->relationLoaded('contexts') ? $quiz->contexts : collect();
        $questions = $quiz->relationLoaded('questions') ? $quiz->questions : collect();
        $conflictCards = $quiz->relationLoaded('conflictCards') ? $quiz->conflictCards : collect();
        $mapZones = $quiz->relationLoaded('mapZones') ? $quiz->mapZones : collect();
        $mapEvents = $quiz->relationLoaded('mapEvents') ? $quiz->mapEvents : collect();
        $matchingChoices = $quiz->relationLoaded('matchingChoices') ? $quiz->matchingChoices : collect();
        $matchingItems = $quiz->relationLoaded('matchingItems') ? $quiz->matchingItems : collect();
        $wordHelpers = $quiz->relationLoaded('evaluationWordHelpers') ? $quiz->evaluationWordHelpers : collect();
        $evaluationItems = $quiz->relationLoaded('evaluationItems') ? $quiz->evaluationItems : collect();
        $reflectionPrompts = $quiz->relationLoaded('reflectionPrompts') ? $quiz->reflectionPrompts : collect();

        $itemsCount = match ($quiz->type->value) {
            'color_the_conflict' => $conflictCards->count(),
            'the_conflict_map' => $mapEvents->count(),
            'matching_type' => $matchingItems->count(),
            'evaluation' => $evaluationItems->count(),
            'reflection' => $reflectionPrompts->count(),
            default => $questions->count(),
        };

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'slug' => $quiz->slug,
            'type' => $quiz->type->value,
            'description' => $quiz->description,
            'reading_passage' => $contexts->first()?->scenario ?? $quiz->reading_passage,
            'contexts' => $contexts->map(static fn (QuizContext $ctx): array => [
                'id' => $ctx->id,
                'scenario' => $ctx->scenario,
                'hint' => $ctx->hint,
            ])->values()->all(),
            'xp_reward' => $quiz->xp_reward,
            'passing_percentage' => $quiz->passing_percentage,
            'time_limit_seconds' => $quiz->time_limit_seconds,
            'sort_order' => $quiz->sort_order,
            'questions_count' => max(1, $itemsCount),
            'questions' => $questions->map(static fn (QuizQuestion $question): array => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'selection_type' => $question->selection_type->value,
                'passage_context' => $question->passage_context,
                'hint' => $question->hint,
                'points' => $question->points,
                'sort_order' => $question->sort_order,
                'options' => ($question->relationLoaded('options') ? $question->options : collect())->map(static fn (QuizQuestionOption $option): array => [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'feedback' => $option->feedback,
                    'sort_order' => $option->sort_order,
                ])->values()->all(),
            ])->values()->all(),
            'cards' => $conflictCards->map(static fn (ColorConflictCard $card): array => [
                'id' => $card->id,
                'excerpt' => $card->excerpt,
                'hint' => $card->hint,
                'points' => $card->points,
                'sort_order' => $card->sort_order,
            ])->values()->all(),
            'zones' => $mapZones->map(static fn (ConflictMapZone $zone): array => [
                'id' => $zone->id,
                'conflict_type' => $zone->conflict_type->value,
                'label' => $zone->label,
                'subtitle' => $zone->subtitle,
                'color_theme' => $zone->color_theme ?? 'blue',
                'sort_order' => $zone->sort_order,
            ])->values()->all(),
            'events' => $mapEvents->map(static fn (ConflictMapEvent $event): array => [
                'id' => $event->id,
                'card_letter' => $event->card_letter,
                'event_text' => $event->event_text,
                'hint' => $event->hint,
                'points' => $event->points,
                'sort_order' => $event->sort_order,
            ])->values()->all(),
            'choices' => $matchingChoices->map(static fn (MatchingChoice $choice): array => [
                'id' => $choice->id,
                'choice_letter' => $choice->choice_letter,
                'choice_text' => $choice->choice_text,
            ])->values()->all(),
            'matching_items' => $matchingItems->map(static fn (MatchingItem $item): array => [
                'id' => $item->id,
                'item_number' => $item->item_number,
                'description' => $item->description,
                'hint' => $item->hint,
                'points' => $item->points,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
            'word_helpers' => $wordHelpers->map(static fn (EvaluationWordHelper $wh): string => $wh->word)->values()->all(),
            'evaluation_items' => $evaluationItems->map(static fn (EvaluationItem $item): array => [
                'id' => $item->id,
                'section_title' => $item->section_title,
                'conflict_type' => $item->conflict_type,
                'question_prompt' => $item->question_prompt,
                'step1_prompt' => $item->step1_prompt ?? 'Step 1: Pick one option:',
                'sentence_template' => $item->sentence_template,
                'hint' => $item->hint,
                'points' => $item->points,
                'sort_order' => $item->sort_order,
                'options' => ($item->relationLoaded('options') ? $item->options : collect())->map(static fn (EvaluationItemOption $opt): array => [
                    'id' => $opt->id,
                    'option_text' => $opt->option_text,
                    'stance_value' => $opt->stance_value,
                ])->values()->all(),
            ])->values()->all(),
            'prompts' => $reflectionPrompts->map(static fn (ReflectionPrompt $prompt): array => [
                'id' => $prompt->id,
                'prompt_text' => $prompt->prompt_text,
                'min_words' => $prompt->min_words,
                'max_words' => $prompt->max_words,
                'guiding_hint' => $prompt->guiding_hint,
                'starter_sentence' => $prompt->starter_sentence,
                'points' => $prompt->points,
                'sort_order' => $prompt->sort_order,
            ])->values()->all(),
        ];
    }

    /**
     * Stale-While-Revalidate read with jittered TTLs, intra-request memoization, and cold-miss lock coalescing.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $resolver
     * @return TValue
     */
    private function rememberFlexible(string $key, int $freshSeconds, int $staleSeconds, Closure $resolver): mixed
    {
        $fresh = $this->jitteredTtl($freshSeconds);
        $stale = max($fresh + 30, $this->jitteredTtl($staleSeconds));
        $createdKey = self::FLEXIBLE_CREATED_PREFIX.$key;

        $memo = Cache::memo();
        $found = $memo->many([$key, $createdKey]);
        $value = $found[$key] ?? null;
        $created = $found[$createdKey] ?? null;

        if ($value !== null && $created !== null) {
            return $memo->flexible($key, [$fresh, $stale], $resolver, ['seconds' => 10]);
        }

        return Cache::lock("cold:{$key}", 10)->block(3, static function () use ($memo, $key, $createdKey, $stale, $resolver) {
            $recheck = Cache::many([$key, $createdKey]);
            if (($recheck[$key] ?? null) !== null && ($recheck[$createdKey] ?? null) !== null) {
                $memo->forget($key);
                $memo->forget($createdKey);

                return $recheck[$key];
            }

            $computed = $resolver();

            $memo->putMany([
                $key => $computed,
                $createdKey => time(),
            ], $stale);

            return $computed;
        });
    }

    /**
     * Singleflight cache-aside read with jittered TTL and cold-miss lock coalescing.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $resolver
     * @return TValue
     */
    private function rememberSingleflight(string $key, int $baseTtlSeconds, Closure $resolver): mixed
    {
        $memo = Cache::memo();
        $sentinel = new \stdClass;
        $cached = $memo->get($key);

        if ($cached !== null) {
            return $cached === '__EMODULE_NULL__' ? null : $cached;
        }

        $ttl = $this->jitteredTtl($baseTtlSeconds);

        $resolved = Cache::lock("cold:{$key}", 10)->block(3, static function () use ($memo, $key, $ttl, $resolver, $sentinel) {
            $recheck = Cache::get($key, $sentinel);
            if ($recheck !== $sentinel) {
                $memo->forget($key);

                return $recheck;
            }

            $computed = $resolver();
            $storable = $computed === null ? '__EMODULE_NULL__' : $computed;
            $memo->put($key, $storable, $ttl);

            return $storable;
        });

        return $resolved === '__EMODULE_NULL__' ? null : $resolved;
    }

    /**
     * Read or initialize a monotonic epoch key using Cache::memo() for intra-request reuse.
     */
    private function getEpoch(string $epochKey): int
    {
        $memo = Cache::memo();
        $value = $memo->get($epochKey);

        if (is_numeric($value)) {
            return (int) $value;
        }

        $initial = (int) round(microtime(true) * 1000);
        if (Cache::add($epochKey, $initial, self::EPOCH_TTL_SECONDS)) {
            $memo->put($epochKey, $initial, self::EPOCH_TTL_SECONDS);

            return $initial;
        }

        $memo->forget($epochKey);
        $existing = $memo->get($epochKey);

        return is_numeric($existing) ? (int) $existing : $initial;
    }

    /**
     * Atomically increment an epoch key (or initialize if missing) and clear the request memo layer.
     */
    private function bumpEpoch(string $epochKey): int
    {
        $memo = Cache::memo();
        $next = $memo->increment($epochKey);

        if ($next !== false && is_numeric($next)) {
            return (int) $next;
        }

        $initial = (int) round(microtime(true) * 1000) + 1;
        if (! Cache::add($epochKey, $initial, self::EPOCH_TTL_SECONDS)) {
            $retried = $memo->increment($epochKey);

            return $retried !== false && is_numeric($retried) ? (int) $retried : $initial;
        }

        $memo->put($epochKey, $initial, self::EPOCH_TTL_SECONDS);

        return $initial;
    }

    /**
     * Add 0-20% uniform jitter to prevent synchronized cache expiry stampedes.
     */
    private function jitteredTtl(int $baseSeconds): int
    {
        $maxJitter = max(1, (int) floor($baseSeconds * 0.2));

        return $baseSeconds + random_int(0, $maxJitter);
    }

    private function catalogEpochKey(): string
    {
        return self::KEY_PREFIX.':epoch:catalog';
    }

    private function moduleEpochKey(int $moduleId): string
    {
        return self::KEY_PREFIX.":epoch:module:{$moduleId}";
    }

    private function studentModuleEpochKey(int $userId, int $moduleId): string
    {
        return self::KEY_PREFIX.":epoch:student:{$userId}:m:{$moduleId}";
    }

    private function studentEnrollmentEpochKey(int $userId): string
    {
        return self::KEY_PREFIX.":epoch:student:{$userId}:enrollments";
    }

    private function teacherGradingEpochKey(int $teacherId): string
    {
        return self::KEY_PREFIX.":epoch:teacher:{$teacherId}:grading";
    }

    private function quizAnalyticsEpochKey(int $quizId): string
    {
        return self::KEY_PREFIX.":epoch:quiz:{$quizId}:analytics";
    }

    public function invalidateTeacherClassrooms(int $teacherId): void
    {
        $this->bumpEpoch($this->teacherClassroomsEpochKey($teacherId));
    }

    public function invalidateClassroom(int $classroomId, ?int $teacherId = null): void
    {
        $this->bumpEpoch($this->classroomEpochKey($classroomId));
        if ($teacherId !== null) {
            $this->bumpEpoch($this->teacherClassroomsEpochKey($teacherId));
        }
    }

    private function teacherClassroomsEpochKey(int $teacherId): string
    {
        return self::KEY_PREFIX.":epoch:teacher:{$teacherId}:classrooms";
    }

    private function classroomEpochKey(int $classroomId): string
    {
        return self::KEY_PREFIX.":epoch:classroom:{$classroomId}";
    }

    private function rankingEpochKey(): string
    {
        return self::KEY_PREFIX.':epoch:users:ranking';
    }
}
