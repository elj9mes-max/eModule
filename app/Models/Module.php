<?php

namespace App\Models;

use App\Enums\ModuleStatus;
use App\Enums\ModuleTheme;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'title',
    'slug',
    'grade_level',
    'description',
    'theme',
    'icon',
    'cover_image_path',
    'xp_bonus',
    'boss_name',
    'max_students',
    'status',
    'sort_order',
    'published_at',
])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'theme' => ModuleTheme::Forest,
        'icon' => '📘',
        'xp_bonus' => 250,
        'max_students' => 35,
        'status' => ModuleStatus::Draft,
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
            'theme' => ModuleTheme::class,
            'status' => ModuleStatus::class,
            'xp_bonus' => 'integer',
            'max_students' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the teacher who owns the module.
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the lectures inside the module, ordered by sort_order.
     *
     * @return HasMany<Lecture, $this>
     */
    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class)->orderBy('sort_order');
    }

    /**
     * Resolve the public URL for the module's cover banner image.
     */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }

    /**
     * Scope a query to only include published modules.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', ModuleStatus::Published);
    }
}
