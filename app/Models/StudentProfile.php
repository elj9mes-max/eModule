<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'total_xp',
    'total_stars',
    'total_gems',
    'gems_spent',
    'equipped_avatar_item_id',
    'equipped_background_item_id',
    'equipped_console_item_id',
    'level',
    'quizzes_passed_count',
    'lectures_completed_count',
    'last_active_at',
])]
class StudentProfile extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_xp' => 0,
        'total_stars' => 0,
        'total_gems' => 0,
        'gems_spent' => 0,
        'level' => 1,
        'quizzes_passed_count' => 0,
        'lectures_completed_count' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'total_xp' => 'integer',
            'total_stars' => 'integer',
            'total_gems' => 'integer',
            'gems_spent' => 'integer',
            'equipped_avatar_item_id' => 'integer',
            'equipped_background_item_id' => 'integer',
            'equipped_console_item_id' => 'integer',
            'level' => 'integer',
            'quizzes_passed_count' => 'integer',
            'lectures_completed_count' => 'integer',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * Gems the student can spend right now: lifetime earned minus everything spent.
     */
    public function gemBalance(): int
    {
        return max(0, (int) $this->total_gems - (int) $this->gems_spent);
    }

    /**
     * The purchased custom avatar the student has equipped.
     *
     * @return BelongsTo<ShopItem, $this>
     */
    public function equippedAvatarItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'equipped_avatar_item_id');
    }

    /**
     * The purchased header background (console skin) the student has equipped.
     *
     * @return BelongsTo<ShopItem, $this>
     */
    public function equippedConsoleItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'equipped_console_item_id');
    }

    /**
     * The purchased expedition trail theme (background) the student has equipped.
     *
     * @return BelongsTo<ShopItem, $this>
     */
    public function equippedBackgroundItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'equipped_background_item_id');
    }

    /**
     * The header skin id of the equipped console item, if any.
     */
    public function equippedHeaderSkin(): ?string
    {
        $skin = $this->equippedConsoleItem?->metadata['header_skin'] ?? null;

        return is_string($skin) ? $skin : null;
    }

    /**
     * The trail theme id of the equipped background item, if any.
     */
    public function equippedTrailTheme(): ?string
    {
        $theme = $this->equippedBackgroundItem?->metadata['trail_theme'] ?? null;

        return is_string($theme) ? $theme : null;
    }

    /**
     * Get the user that owns this profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
