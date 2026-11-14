<?php

namespace App\Models;

use App\Enums\ModuleStatus;
use App\Enums\ModuleTheme;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'title',
    'slug',
    'join_code',
    'grade_level',
    'description',
    'theme',
    'icon',
    'cover_image_path',
    'xp_bonus',
    'boss_name',
    'max_students',
    'status',
    'sort_order',
    'published_at',
])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'theme' => ModuleTheme::Forest,
        'icon' => '📘',
        'xp_bonus' => 250,
        'status' => ModuleStatus::Draft,
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
            'theme' => ModuleTheme::class,
            'status' => ModuleStatus::class,
            'xp_bonus' => 'integer',
            'max_students' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the teacher who owns the module.
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the lectures inside the module, ordered by sort_order.
     *
     * @return HasMany<Lecture, $this>
     */
    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class)->orderBy('sort_order');
    }

    /**
     * Get the quizzes inside the module, ordered by sort_order.
     *
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->orderBy('sort_order');
    }

    /**
     * Determine the next sequential milestone sort order across all lectures and quizzes.
     */
    public function nextMilestoneSortOrder(): int
    {
        $maxLectureOrder = (int) $this->lectures()->max('sort_order');
        $maxQuizOrder = (int) $this->quizzes()->max('sort_order');

        return max($maxLectureOrder, $maxQuizOrder) + 1;
    }

    /**
     * Resolve the public URL for the module's cover banner image.
     */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path === null) {
            return null;
        }

        return '/storage/'.ltrim($this->cover_image_path, '/');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Module $module): void {
            if (empty($module->join_code)) {
                $module->join_code = static::generateUniqueJoinCode();
            }
        });
    }

    /**
     * Generate a unique 6-digit random numerical join code.
     */
    public static function generateUniqueJoinCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (static::query()->where('join_code', $code)->exists());

        return $code;
    }

    /**
     * Get the student enrollments for this module.
     *
     * @return HasMany<ModuleEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ModuleEnrollment::class);
    }

    /**
     * Get the students enrolled in this module.
     *
     * @return BelongsToMany<User, $this>
     */
    public function enrolledStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'module_enrollments')
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    /**
     * Classroom module assignment pivot records.
     *
     * @return HasMany<ClassroomModule, $this>
     */
    public function classroomModules(): HasMany
    {
        return $this->hasMany(ClassroomModule::class);
    }

    /**
     * The classrooms this module is assigned to.
     *
     * @return BelongsToMany<Classroom, $this>
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_modules')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include published modules.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', ModuleStatus::Published);
    }

    /**
     * Scope to the published modules a student can currently see: modules assigned to an active classroom
     * the student belongs to, plus modules the student joined directly with the module's own join code.
     *
     * Classroom access is resolved live from classroom membership, so removing a student or a module
     * from a classroom immediately hides it.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function visibleToStudent(Builder $query, int $studentId): Builder
    {
        return $query
            ->where('status', ModuleStatus::Published)
            ->where(function (Builder $access) use ($studentId): void {
                $access
                    ->whereHas('classrooms', fn (Builder $classroom) => $classroom
                        ->where('classrooms.is_active', true)
                        ->whereHas('studentEnrollments', fn (Builder $membership) => $membership->where('user_id', $studentId)))
                    ->orWhereHas('enrollments', fn (Builder $directJoin) => $directJoin->where('user_id', $studentId));
            });
    }
}
