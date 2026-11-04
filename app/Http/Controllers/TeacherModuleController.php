<?php

namespace App\Http\Controllers;

use App\Enums\ModuleStatus;
use App\Http\Requests\StoreModuleRequest;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherModuleController extends Controller
{
    /**
     * Display the form to create a new module.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher()) {
            return redirect()->route('students.dashboard');
        }

        return Inertia::render('teachers/create/Module', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
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

        $user->modules()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'grade_level' => $validated['grade_level'] ?? null,
            'description' => $validated['description'] ?? null,
            'theme' => $validated['theme'],
            'icon' => $validated['icon'],
            'cover_image_path' => $coverImagePath,
            'xp_bonus' => (int) $validated['xp_bonus'],
            'boss_name' => $validated['boss_name'] ?? null,
            'max_students' => (int) $validated['max_students'],
            'status' => $status,
            'sort_order' => $nextOrder,
            'published_at' => $status === ModuleStatus::Published ? now() : null,
        ]);

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

        $module->load([
            'lectures' => fn ($query) => $query->with('contents')->orderBy('sort_order'),
        ]);

        return Inertia::render('teachers/view/Module', [
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
                'description' => $module->description,
                'grade_level' => $module->grade_level ?? 'All Grades • Reading Quest',
                'theme' => $module->theme->value,
                'icon' => $module->icon,
                'cover_image_url' => $module->coverImageUrl(),
                'xp_bonus' => $module->xp_bonus,
                'boss_name' => $module->boss_name ?? 'Chapter Guardian',
                'max_students' => $module->max_students,
                'status' => $module->status->value,
            ],
            'lectures' => $module->lectures->map(fn (Lecture $lecture): array => [
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
                'contents' => $lecture->contents->map(fn (LectureContent $content): array => [
                    'id' => $content->id,
                    'type' => $content->type->value,
                    'title' => $content->title,
                    'content' => $content->content,
                    'media_url' => $content->mediaUrl(),
                    'external_url' => $content->external_url,
                    'caption' => $content->caption,
                    'sort_order' => $content->sort_order,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
