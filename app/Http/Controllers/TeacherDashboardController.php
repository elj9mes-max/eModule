<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Models\Module;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    /**
     * Display the teacher dashboard with modules fetched from the database.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        $modules = $user->modules()
            ->reorder()
            ->latest('created_at')
            ->latest('id')
            ->withCount('lectures')
            ->get()
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
                'lectures_count' => $module->lectures_count,
                'status' => $module->status->value,
                'status_label' => match ($module->status) {
                    ModuleStatus::Published => 'Published',
                    ModuleStatus::Draft => 'Draft Module',
                    ModuleStatus::Archived => 'Archived',
                },
                'is_new' => $module->created_at?->isAfter(now()->subDays(2)) ?? false,
                'created_at' => $module->created_at?->toIso8601String(),
            ]);

        $pendingGradingCount = QuizAttempt::query()
            ->whereHas('quiz', fn ($q) => $q->whereIn('module_id', $user->modules()->pluck('id')))
            ->where('status', 'pending_review')
            ->count();

        return Inertia::render('teachers/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'modules' => $modules,
            'pending_grading_count' => $pendingGradingCount,
        ]);
    }

    /**
     * Display the students ranking page.
     */
    public function students(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        return Inertia::render('teachers/Students', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }
}
