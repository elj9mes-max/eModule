<?php

namespace App\Http\Controllers;

use App\Services\Cache\EModuleCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    public function __construct(
        private readonly EModuleCacheService $cache,
    ) {}

    /**
     * Display the teacher dashboard with modules fetched from the database, optionally filtered by classroom.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $classroomId = $request->query('class_id') ? (int) $request->query('class_id') : null;
        $classrooms = $this->cache->getTeacherClassrooms($user->id);

        $selectedClassroom = null;
        if ($classroomId !== null) {
            $selectedClassroom = collect($classrooms)->firstWhere('id', $classroomId);
            if (! $selectedClassroom) {
                // If invalid classroom ID or doesn't belong to teacher, reset to all
                $classroomId = null;
            }
        }

        return Inertia::render('teachers/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'modules' => $this->cache->getTeacherModules($user->id, $classroomId),
            'classrooms' => $classrooms,
            'selected_classroom_id' => $classroomId,
            'selected_classroom' => $selectedClassroom,
            'pending_grading_count' => $this->cache->getTeacherPendingGradingCount($user->id),
        ]);
    }

    /**
     * Display the students ranking page scoped to this teacher's classrooms.
     */
    public function students(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $classroomId = $request->query('class_id') ? (int) $request->query('class_id') : null;
        $classrooms = $this->cache->getTeacherClassrooms($user->id);
        $students = $this->cache->getTeacherStudents($user->id, $classroomId);

        return Inertia::render('teachers/Students', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'classrooms' => $classrooms,
            'selected_classroom_id' => $classroomId,
            'students' => $students,
            'pending_grading_count' => $this->cache->getTeacherPendingGradingCount($user->id),
        ]);
    }
}
