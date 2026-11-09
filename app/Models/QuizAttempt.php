<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'quiz_id',
        'attempt_number',
        'status',
        'score',
        'max_score',
        'percentage',
        'stars',
        'is_passed',
        'xp_earned',
        'time_spent_seconds',
        'started_at',
        'completed_at',
        'teacher_feedback',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'attempt_number' => 'integer',
        'score' => 'integer',
        'max_score' => 'integer',
        'percentage' => 'decimal:2',
        'stars' => 'integer',
        'is_passed' => 'boolean',
        'xp_earned' => 'integer',
        'time_spent_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the student user who took the attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quiz being attempted.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the granular student answers for this attempt.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'attempt_id');
    }
}
