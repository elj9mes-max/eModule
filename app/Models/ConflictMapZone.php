<?php

namespace App\Models;

use App\Enums\ConflictType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id',
    'conflict_type',
    'label',
    'subtitle',
    'color_theme',
    'sort_order',
])]
class ConflictMapZone extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'color_theme' => 'blue',
        'sort_order' => 1,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conflict_type' => ConflictType::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns this drop zone.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the event cards assigned to this zone.
     */
    public function events(): HasMany
    {
        return $this->hasMany(ConflictMapEvent::class, 'zone_id');
    }
}
