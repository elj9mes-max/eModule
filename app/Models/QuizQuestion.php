<?php

namespace App\Models;

use App\Enums\QuestionSelectionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id',
    'question_text',
    'selection_type',
    'passage_context',
    'image_path',
    'hint',
    'explanation',
    'points',
    'sort_order',
])]
class QuizQuestion extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
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
            'selection_type' => QuestionSelectionType::class,
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns the question.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the answer options for this question.
     *
     * @return HasMany<QuizQuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuizQuestionOption::class, 'question_id')->orderBy('sort_order');
    }
}
