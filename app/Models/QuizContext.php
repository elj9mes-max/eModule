<?php

namespace App\Models;

use Database\Factories\QuizContextFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_id',
    'scenario',
    'hint',
])]
class QuizContext extends Model
{
    /** @use HasFactory<QuizContextFactory> */
    use HasFactory;

    /**
     * Get the quiz that owns this scenario context.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
