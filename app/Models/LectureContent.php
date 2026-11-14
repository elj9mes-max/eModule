<?php

namespace App\Models;

use App\Enums\LectureContentType;
use App\Enums\PaperTheme;
use Database\Factories\LectureContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lecture_id',
    'type',
    'layout_style',
    'paper_theme',
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
    'narration_audio_disk',
    'narration_audio_path',
    'narration_audio_name',
    'vocabulary_glosses',
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
        'layout_style' => 'standard',
        'paper_theme' => PaperTheme::Classic,
        'file_disk' => 'public',
        'narration_audio_disk' => 'public',
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
            'paper_theme' => PaperTheme::class,
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'vocabulary_glosses' => 'array',
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
            return '/storage/'.ltrim($this->file_path, '/');
        }

        return $this->external_url;
    }

    /**
     * Resolve the public URL for the full-page Read-Aloud narration audio track.
     */
    public function narrationAudioUrl(): ?string
    {
        if ($this->narration_audio_path !== null) {
            return '/storage/'.ltrim($this->narration_audio_path, '/');
        }

        return null;
    }
}
