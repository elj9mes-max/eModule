<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'total_xp',
    'total_stars',
    'level',
    'quizzes_passed_count',
    'lectures_completed_count',
    'last_active_at',
])]
class StudentProfile extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_xp' => 0,
        'total_stars' => 0,
        'level' => 1,
        'quizzes_passed_count' => 0,
        'lectures_completed_count' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'total_xp' => 'integer',
            'total_stars' => 'integer',
            'level' => 'integer',
            'quizzes_passed_count' => 'integer',
            'lectures_completed_count' => 'integer',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
