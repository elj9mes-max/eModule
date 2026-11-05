<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_id',
    'prompt_text',
    'max_words',
    'min_words',
    'guiding_hint',
    'starter_sentence',
    'points',
    'sort_order',
])]
class ReflectionPrompt extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'max_words' => 150,
        'points' => 50,
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
            'max_words' => 'integer',
            'min_words' => 'integer',
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns this reflection prompt.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
