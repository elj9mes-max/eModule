<?php

namespace App\Models;

use App\Enums\LectureContentType;
use Database\Factories\LectureContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'lecture_id',
    'type',
    'title',
    'content',
    'file_disk',
    'file_path',
    'external_url',
    'mime_type',
    'file_size',
    'duration_seconds',
    'alt_text',
    'caption',
    'metadata',
    'sort_order',
])]
class LectureContent extends Model
{
    /** @use HasFactory<LectureContentFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => LectureContentType::Text,
        'file_disk' => 'public',
        'sort_order' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LectureContentType::class,
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the lecture that owns this content block.
     *
     * @return BelongsTo<Lecture, $this>
     */
    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    /**
     * Resolve the public URL for an uploaded media file or external link.
     */
    public function mediaUrl(): ?string
    {
        if ($this->file_path !== null) {
            return Storage::disk($this->file_disk ?? 'public')->url($this->file_path);
        }

        return $this->external_url;
    }
}
