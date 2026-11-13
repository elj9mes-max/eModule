<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherClassroomController extends Controller
{
    public function __construct(
        private readonly EModuleCacheService $cache,
    ) {}

    /**
     * Display the list of classrooms and rooms created by the teacher.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $classrooms = $this->cache->getTeacherClassrooms($user->id);
        $modules = $this->cache->getTeacherModules($user->id);

        return Inertia::render('teachers/Classes', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'classrooms' => $classrooms,
            'pending_grading_count' => $this->cache->getTeacherPendingGradingCount($user->id),
            'available_modules' => array_map(static fn (array $m): array => [
                'id' => $m['id'],
                'title' => $m['title'],
                'slug' => $m['slug'],
                'grade_level' => $m['grade_level'],
                'theme' => $m['theme'],
                'icon' => $m['icon'],
            ], $modules),
        ]);
    }

    /**
     * Store a newly created classroom room in storage.
     */
    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $classroom = Classroom::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'grade_level' => $validated['grade_level'] ?? 'All Grades • Reading Legends',
            'theme' => $validated['theme'] ?? 'forest',
            'icon' => $validated['icon'] ?? '🏫',
            'description' => $validated['description'] ?? null,
            'max_students' => $validated['max_students'] ?? 45,
            'is_active' => true,
        ]);

        if (! empty($validated['module_ids'])) {
            $modules = Module::where('user_id', $user->id)
                ->whereIn('id', $validated['module_ids'])
                ->get();

            foreach ($modules as $mod) {
                $classroom->assignModule($mod);
            }
        }

        $this->cache->invalidateTeacherClassrooms($user->id);

        return redirect()->route('teachers.classes.index')->with('success', "Room \"{$classroom->name}\" created with Quest Code: {$classroom->class_code}!");
    }

    /**
     * Assign a learning module to this classroom.
     */
    public function assignModule(Request $request, Classroom $classroom): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $classroom->user_id !== $user->id) {
            abort(403);
        }

        $moduleId = (int) $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
        ])['module_id'];

        $module = Module::where('id', $moduleId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $classroom->assignModule($module);

        $this->cache->invalidateTeacherClassrooms($user->id);
        $this->cache->invalidateClassroom($classroom->id, $user->id);

        return back()->with('success', "Module \"{$module->title}\" assigned to \"{$classroom->name}\"!");
    }

    /**
     * Remove an assigned module from this classroom.
     */
    public function removeModule(Request $request, Classroom $classroom, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $classroom->user_id !== $user->id || $module->user_id !== $user->id) {
            abort(403);
        }

        $classroom->classroomModules()->where('module_id', $module->id)->delete();

        $this->cache->invalidateTeacherClassrooms($user->id);
        $this->cache->invalidateClassroom($classroom->id, $user->id);

        return back()->with('info', "Module \"{$module->title}\" removed from \"{$classroom->name}\".");
    }

    /**
     * Remove a student from this classroom roster.
     */
    public function removeStudent(Request $request, Classroom $classroom, User $student): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $classroom->user_id !== $user->id) {
            abort(403);
        }

        $classroom->studentEnrollments()->where('user_id', $student->id)->delete();

        $this->cache->invalidateStudentEnrollment($student->id);
        $this->cache->invalidateClassroom($classroom->id, $user->id);

        return back()->with('info', "Student {$student->name} removed from {$classroom->name}.");
    }
}
