<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id',
    'section_title',
    'conflict_type',
    'question_prompt',
    'step1_prompt',
    'sentence_template',
    'hint',
    'points',
    'sort_order',
])]
class EvaluationItem extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'step1_prompt' => 'Step 1: Pick one option:',
        'points' => 25,
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
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns this evaluation item.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the Step 1 options for this item.
     */
    public function options(): HasMany
    {
        return $this->hasMany(EvaluationItemOption::class, 'item_id')->orderBy('sort_order');
    }
}
