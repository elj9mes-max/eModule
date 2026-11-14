<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait ResolvesPublicFileUrls
{
    /**
     * Resolve the browser URL for a file stored on a filesystem disk.
     *
     * Local disks return a root-relative "/storage/..." path so the app also works when opened from
     * another device on the network (where APP_URL's host would not resolve). Remote disks, such as a
     * Laravel Cloud bucket, return the disk's own public URL.
     */
    protected function publicFileUrl(string $path, ?string $disk = null): string
    {
        $disk ??= 'public';
        $relativePath = ltrim($path, '/');

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return '/storage/'.$relativePath;
        }

        return Storage::disk($disk)->url($relativePath);
    }
}
