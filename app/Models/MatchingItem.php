<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_id',
    'correct_choice_id',
    'item_number',
    'description',
    'hint',
    'explanation',
    'points',
    'sort_order',
])]
class MatchingItem extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'points' => 10,
        'item_number' => 1,
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
            'item_number' => 'integer',
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns this item.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the correct choice from Column B for this item.
     */
    public function correctChoice(): BelongsTo
    {
        return $this->belongsTo(MatchingChoice::class, 'correct_choice_id');
    }
}
