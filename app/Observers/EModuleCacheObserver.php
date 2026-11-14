<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\ClassroomStudent;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Database\Eloquent\Model;

class EModuleCacheObserver
{
    public function __construct(
        private readonly EModuleCacheService $cache,
    ) {}

    /**
     * Invalidate affected cache epochs whenever a tracked model is created or updated.
     */
    public function saved(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    /**
     * Invalidate affected cache epochs whenever a tracked model is deleted.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    /**
     * Dispatch targeted epoch bumps without triggering lazy relationship loads.
     */
    private function invalidateForModel(Model $model): void
    {
        if ($model instanceof Module) {
            $this->cache->invalidateModule((int) $model->id, bumpCatalog: true);

            return;
        }

        if ($model instanceof Lecture || $model instanceof Quiz) {
            $this->cache->invalidateModule((int) $model->module_id, bumpCatalog: true);

            return;
        }

        if ($model instanceof LectureContent) {
            $moduleId = Lecture::query()->whereKey($model->lecture_id)->value('module_id');
            if ($moduleId !== null) {
                $this->cache->invalidateModule((int) $moduleId, bumpCatalog: false);
            }

            return;
        }

        if ($model instanceof StudentLectureCompletion) {
            $moduleId = Lecture::query()->whereKey($model->lecture_id)->value('module_id');
            if ($moduleId !== null) {
                $this->cache->invalidateStudentModuleProgress((int) $model->user_id, (int) $moduleId);
            }

            return;
        }

        if ($model instanceof QuizAttempt) {
            $meta = Quiz::query()
                ->join('modules', 'modules.id', '=', 'quizzes.module_id')
                ->where('quizzes.id', $model->quiz_id)
                ->first(['quizzes.module_id as module_id', 'modules.user_id as teacher_id']);

            if ($meta !== null) {
                $this->cache->invalidateAttemptProgress(
                    (int) $model->user_id,
                    (int) $meta->module_id,
                    (int) $meta->teacher_id,
                    (int) $model->quiz_id,
                );
            }

            return;
        }

        if ($model instanceof ModuleEnrollment || $model instanceof ClassroomStudent) {
            $this->cache->invalidateStudentEnrollment((int) $model->user_id);

            return;
        }

        if ($model instanceof ClassroomModule) {
            $this->cache->invalidateClassroom((int) $model->classroom_id);
            $this->cache->invalidateClassroomStudentModules((int) $model->classroom_id);

            return;
        }

        if ($model instanceof Classroom) {
            $this->cache->invalidateClassroom((int) $model->id);
            $this->cache->invalidateClassroomStudentModules((int) $model->id);

            return;
        }

        if ($model instanceof StudentProfile) {
            $this->cache->invalidateRanking();

            return;
        }

        if ($model instanceof User) {
            if ($model->role === UserRole::Student || $model->wasChanged('role') || $model->wasChanged('name')) {
                $this->cache->invalidateRanking();
            }
        }
    }
}
