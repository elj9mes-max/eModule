<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'grade_level',
        'class_code',
        'theme',
        'icon',
        'description',
        'max_students',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_students' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (self $classroom): void {
            if (empty($classroom->class_code)) {
                $classroom->class_code = self::generateUniqueClassCode();
            }
        });
    }

    /**
     * Generate a unique 6-digit numerical code for the classroom.
     */
    public static function generateUniqueClassCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::query()->where('class_code', $code)->exists());

        return $code;
    }

    /**
     * The teacher who owns and manages this classroom.
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Classroom student enrollment pivot records.
     *
     * @return HasMany<ClassroomStudent, $this>
     */
    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    /**
     * The students enrolled in this classroom.
     *
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_students')
            ->withPivot('joined_at')
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
     * The learning modules assigned to this classroom.
     *
     * @return BelongsToMany<Module, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'classroom_modules')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Assign a module to this classroom.
     *
     * Students are not copied into module_enrollments: classroom members see the module through
     * Module::visibleToStudent(), so access always follows current classroom membership.
     */
    public function assignModule(Module|int $module): ClassroomModule
    {
        $moduleId = $module instanceof Module ? $module->id : $module;

        /** @var ClassroomModule $assignment */
        $assignment = $this->classroomModules()->firstOrCreate([
            'module_id' => $moduleId,
        ], [
            'assigned_at' => now(),
        ]);

        return $assignment;
    }

    /**
     * Add a student to this classroom. They gain the classroom's modules through membership alone.
     */
    public function enrollStudent(User|int $student): ClassroomStudent
    {
        $studentId = $student instanceof User ? $student->id : $student;

        /** @var ClassroomStudent $enrollment */
        $enrollment = $this->studentEnrollments()->firstOrCreate([
            'user_id' => $studentId,
        ], [
            'joined_at' => now(),
        ]);

        return $enrollment;
    }
}
