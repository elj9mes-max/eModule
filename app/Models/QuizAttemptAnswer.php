<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attempt_id',
        'item_type',
        'item_id',
        'answer_data',
        'answer_text',
        'is_correct',
        'points_earned',
        'points_possible',
        'teacher_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'item_id' => 'integer',
        'answer_data' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
        'points_possible' => 'integer',
    ];

    /**
     * Get the parent quiz attempt.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }
}
