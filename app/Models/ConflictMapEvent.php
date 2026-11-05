<?php

namespace App\Models;

use App\Enums\ConflictType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_id',
    'zone_id',
    'card_letter',
    'event_text',
    'target_conflict_type',
    'hint',
    'explanation',
    'points',
    'sort_order',
])]
class ConflictMapEvent extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'points' => 10,
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
            'target_conflict_type' => ConflictType::class,
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns this event card.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the drop zone this event belongs to.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ConflictMapZone::class, 'zone_id');
    }
}
