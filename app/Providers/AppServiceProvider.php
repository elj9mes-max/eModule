<?php

namespace App\Providers;

use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentLectureCompletion;
use App\Models\User;
use App\Observers\EModuleCacheObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(app()->isLocal());

        foreach ([
            Module::class,
            Lecture::class,
            LectureContent::class,
            Quiz::class,
            QuizAttempt::class,
            StudentLectureCompletion::class,
            User::class,
        ] as $modelClass) {
            $modelClass::observe(EModuleCacheObserver::class);
        }
    }
}
