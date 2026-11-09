<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLectureCompletion extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'lecture_id',
        'xp_earned',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'xp_earned' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * The student who completed the lecture.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The lecture that was completed.
     */
    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }
}
