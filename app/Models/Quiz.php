<?php

namespace App\Models;

use App\Enums\QuizType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'module_id',
    'lecture_id',
    'type',
    'title',
    'slug',
    'description',
    'reading_passage',
    'xp_reward',
    'passing_percentage',
    'time_limit_seconds',
    'sort_order',
    'is_published',
    'published_at',
])]
class Quiz extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => QuizType::MultipleChoice,
        'xp_reward' => 150,
        'passing_percentage' => 70,
        'is_published' => true,
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
            'type' => QuizType::class,
            'xp_reward' => 'integer',
            'passing_percentage' => 'integer',
            'time_limit_seconds' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the module that owns the quiz.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the optional lecture associated with the quiz.
     *
     * @return BelongsTo<Lecture, $this>
     */
    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    /**
     * Get all questions for the quiz.
     *
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    /**
     * Get the scenario context for this quiz.
     *
     * @return HasOne<QuizContext, $this>
     */
    public function context(): HasOne
    {
        return $this->hasOne(QuizContext::class);
    }

    /**
     * Get all scenario contexts for this quiz.
     *
     * @return HasMany<QuizContext, $this>
     */
    public function contexts(): HasMany
    {
        return $this->hasMany(QuizContext::class);
    }

    /**
     * Get the conflict classification cards for this quiz.
     *
     * @return HasMany<ColorConflictCard, $this>
     */
    public function conflictCards(): HasMany
    {
        return $this->hasMany(ColorConflictCard::class)->orderBy('sort_order');
    }

    /**
     * Get the conflict map drop zones for this quiz.
     *
     * @return HasMany<ConflictMapZone, $this>
     */
    public function mapZones(): HasMany
    {
        return $this->hasMany(ConflictMapZone::class)->orderBy('sort_order');
    }

    /**
     * Get the conflict map event cards for this quiz.
     *
     * @return HasMany<ConflictMapEvent, $this>
     */
    public function mapEvents(): HasMany
    {
        return $this->hasMany(ConflictMapEvent::class)->orderBy('sort_order');
    }

    /**
     * Get the matching type choices (Column B) for this quiz.
     *
     * @return HasMany<MatchingChoice, $this>
     */
    public function matchingChoices(): HasMany
    {
        return $this->hasMany(MatchingChoice::class)->orderBy('sort_order');
    }

    /**
     * Get the matching type items (Column A) for this quiz.
     *
     * @return HasMany<MatchingItem, $this>
     */
    public function matchingItems(): HasMany
    {
        return $this->hasMany(MatchingItem::class)->orderBy('sort_order');
    }

    /**
     * Get the word helper chips for this evaluation quiz.
     *
     * @return HasMany<EvaluationWordHelper, $this>
     */
    public function evaluationWordHelpers(): HasMany
    {
        return $this->hasMany(EvaluationWordHelper::class)->orderBy('sort_order');
    }

    /**
     * Get the multi-part evaluation items for this quiz.
     *
     * @return HasMany<EvaluationItem, $this>
     */
    public function evaluationItems(): HasMany
    {
        return $this->hasMany(EvaluationItem::class)->orderBy('sort_order');
    }

    /**
     * Get the reflection prompts for this quiz.
     *
     * @return HasMany<ReflectionPrompt, $this>
     */
    public function reflectionPrompts(): HasMany
    {
        return $this->hasMany(ReflectionPrompt::class)->orderBy('sort_order');
    }
}
