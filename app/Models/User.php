<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PixarWorldTheme;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'ui_theme'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Student,
        'ui_theme' => PixarWorldTheme::Toybox,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'ui_theme' => PixarWorldTheme::class,
        ];
    }

    /**
     * Get the modules created by the teacher.
     *
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->latest('created_at')->latest('id');
    }

    /**
     * Determine if the user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    /**
     * Determine if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    /**
     * Get all quiz attempts submitted by this user.
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class)->latest('id');
    }

    /**
     * Get all lecture completions by this student.
     *
     * @return HasMany<StudentLectureCompletion, $this>
     */
    public function lectureCompletions(): HasMany
    {
        return $this->hasMany(StudentLectureCompletion::class)->latest('id');
    }

    /**
     * Get the module enrollments for this student.
     *
     * @return HasMany<ModuleEnrollment, $this>
     */
    public function moduleEnrollments(): HasMany
    {
        return $this->hasMany(ModuleEnrollment::class);
    }

    /**
     * Get the modules this student is enrolled in.
     *
     * @return BelongsToMany<Module, $this>
     */
    public function enrolledModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_enrollments')
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    /**
     * Get the student's gamification profile.
     *
     * @return HasOne<StudentProfile, $this>
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * The classrooms taught by this teacher.
     *
     * @return HasMany<Classroom, $this>
     */
    public function taughtClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class)->latest('created_at')->latest('id');
    }

    /**
     * The classroom enrollments for this student.
     *
     * @return HasMany<ClassroomStudent, $this>
     */
    public function classroomEnrollments(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    /**
     * The classrooms this student is enrolled in.
     *
     * @return BelongsToMany<Classroom, $this>
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_students')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * Resolve or atomically create the student's gamification profile.
     */
    public function getOrCreateStudentProfile(): StudentProfile
    {
        return $this->studentProfile ?? $this->studentProfile()->firstOrCreate([
            'user_id' => $this->id,
        ]);
    }
}
