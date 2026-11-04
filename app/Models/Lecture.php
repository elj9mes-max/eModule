<?php

namespace App\Models;

use Database\Factories\LectureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'module_id',
    'title',
    'slug',
    'description',
    'body',
    'estimated_duration_minutes',
    'xp_reward',
    'sort_order',
    'is_published',
    'published_at',
])]
class Lecture extends Model
{
    /** @use HasFactory<LectureFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'xp_reward' => 100,
        'sort_order' => 0,
        'is_published' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_duration_minutes' => 'integer',
            'xp_reward' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the module that owns the lecture.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the ordered multimedia and text content blocks for the lecture.
     *
     * @return HasMany<LectureContent, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(LectureContent::class)->orderBy('sort_order');
    }

    /**
     * Scope a query to only include published lectures.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
