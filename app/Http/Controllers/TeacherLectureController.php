<?php

namespace App\Http\Controllers;

use App\Enums\LectureContentType;
use App\Http\Requests\StoreLectureContentRequest;
use App\Http\Requests\StoreLectureRequest;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeacherLectureController extends Controller
{
    /**
     * Display the form to create a new lecture under a module.
     */
    public function create(Request $request, Module $module): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $nextOrder = $module->nextMilestoneSortOrder();

        return Inertia::render('teachers/create/Lecture', [
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
     * Store a newly created lecture in storage.
     */
    public function store(StoreLectureRequest $request, Module $module): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $baseSlug = Str::slug($validated['title']) ?: 'lecture';
        $slug = $baseSlug;
        $counter = 1;

        while ($module->lectures()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $nextOrder = $module->nextMilestoneSortOrder();
        $isPublished = (bool) ($validated['is_published'] ?? true);

        $module->lectures()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'] ?? null,
            'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? null,
            'xp_reward' => (int) $validated['xp_reward'],
            'sort_order' => $nextOrder,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);

        return redirect()->route('teachers.modules.show', $module->slug);
    }

    /**
     * Display the form to create and manage lecture content blocks.
     */
    public function createContent(Request $request, Module $module, Lecture $lecture): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $lecture->module_id !== $module->id) {
            return redirect()->route('teachers.dashboard');
        }

        $lecture->load(['contents' => fn ($q) => $q->orderBy('sort_order')]);

        return Inertia::render('teachers/create/LectureContent', [
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
            ],
            'lecture' => [
                'id' => $lecture->id,
                'title' => $lecture->title,
                'slug' => $lecture->slug,
                'description' => $lecture->description,
                'body' => $lecture->body,
                'estimated_duration_minutes' => $lecture->estimated_duration_minutes,
                'xp_reward' => $lecture->xp_reward,
                'sort_order' => $lecture->sort_order,
                'is_published' => $lecture->is_published,
            ],
            'existing_contents' => $lecture->contents->map(fn (LectureContent $content): array => [
                'id' => $content->id,
                'type' => $content->type->value,
                'title' => $content->title,
                'content' => $content->content,
                'media_url' => $content->mediaUrl(),
                'external_url' => $content->external_url,
                'duration_seconds' => $content->duration_seconds,
                'alt_text' => $content->alt_text,
                'caption' => $content->caption,
                'sort_order' => $content->sort_order,
                'created_at' => $content->created_at?->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Store a newly created content block under a lecture.
     */
    public function storeContent(StoreLectureContentRequest $request, Module $module, Lecture $lecture): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isTeacher() || $module->user_id !== $user->id || $lecture->module_id !== $module->id) {
            return redirect()->route('teachers.dashboard');
        }

        $validated = $request->validated();
        $filePath = null;
        $fileDisk = 'public';
        $mimeType = null;
        $fileSize = null;

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $typeString = $validated['type'] instanceof LectureContentType
                ? $validated['type']->value
                : (string) $validated['type'];
            $filePath = $file->store("lectures/{$typeString}", $fileDisk);
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
        }

        $nextOrder = ((int) $lecture->contents()->max('sort_order')) + 1;

        $lecture->contents()->create([
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
            'file_disk' => $fileDisk,
            'file_path' => $filePath,
            'external_url' => $validated['external_url'] ?? null,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'alt_text' => $validated['alt_text'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $nextOrder,
        ]);

        return redirect()->route('teachers.lectures.contents.create', [
            'module' => $module->slug,
            'lecture' => $lecture->slug,
        ]);
    }
}
