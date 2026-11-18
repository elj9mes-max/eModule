<?php

namespace App\Services;

use App\Models\Concerns\ResolvesPublicFileUrls;
use App\Models\ConflictMapEvent;
use App\Models\Module;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores and cleans up the pictures teachers attach to Conflict Map event cards.
 *
 * Files live under a per-module folder so a quiz can only reference images uploaded for
 * its own module, and a file is only deleted once no event card (including copies of a
 * quiz) still points at it.
 */
class ConflictMapImageService
{
    use ResolvesPublicFileUrls;

    public const DISK = 'public';

    /**
     * Browser URL for a stored event card image.
     */
    public function url(string $path): string
    {
        return $this->publicFileUrl($path, self::DISK);
    }

    /**
     * Save an uploaded event card image and return its storage path.
     */
    public function store(UploadedFile $image, Module $module): string
    {
        return $image->store($this->directoryFor($module), self::DISK);
    }

    /**
     * Whether a path points at an existing image uploaded for this module.
     */
    public function belongsToModule(string $path, Module $module): bool
    {
        if (str_contains($path, '..') || ! Str::startsWith($path, $this->directoryFor($module).'/')) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Delete the given image files that no event card references anymore.
     *
     * @param  iterable<int, string|null>  $paths
     */
    public function deleteUnreferenced(iterable $paths): void
    {
        $candidates = collect($paths)->filter()->unique()->values();

        if ($candidates->isEmpty()) {
            return;
        }

        $stillUsed = ConflictMapEvent::query()
            ->whereIn('image_path', $candidates)
            ->pluck('image_path')
            ->all();

        $candidates
            ->diff($stillUsed)
            ->each(fn (string $path): bool => Storage::disk(self::DISK)->delete($path));
    }

    /**
     * Folder that holds a module's event card images.
     */
    public function directoryFor(Module $module): string
    {
        return "quizzes/conflict-map/{$module->id}";
    }
}
