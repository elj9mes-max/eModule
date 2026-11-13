<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentQuizController;
use App\Http\Controllers\TeacherAiAssistantController;
use App\Http\Controllers\TeacherClassroomController;
use App\Http\Controllers\TeacherColorConflictQuizController;
use App\Http\Controllers\TeacherConflictMapQuizController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherEvaluationQuizController;
use App\Http\Controllers\TeacherGradingController;
use App\Http\Controllers\TeacherLectureController;
use App\Http\Controllers\TeacherMatchingTypeQuizController;
use App\Http\Controllers\TeacherModuleController;
use App\Http\Controllers\TeacherMultipleChoiceQuizController;
use App\Http\Controllers\TeacherQuizResultController;
use App\Http\Controllers\TeacherReflectionQuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('guest');
Route::get('/signup', [RegisteredUserController::class, 'create'])->middleware('guest')->name('signup');
Route::get('/register', [RegisteredUserController::class, 'create'])->middleware('guest');
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest')->name('register');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/teachers/dashboard', [TeacherDashboardController::class, 'index'])->name('teachers.dashboard');
    Route::get('/teachers/Dashboard', fn () => redirect()->route('teachers.dashboard'));
    Route::get('/teachers/Dasboard', fn () => redirect()->route('teachers.dashboard'));
    Route::get('/teachers/students', [TeacherDashboardController::class, 'students'])->name('teachers.students');
    Route::get('/teachers/Students', fn () => redirect()->route('teachers.students'));
    Route::get('/teachers/classes', [TeacherClassroomController::class, 'index'])->name('teachers.classes.index');
    Route::post('/teachers/classes', [TeacherClassroomController::class, 'store'])->name('teachers.classes.store');
    Route::post('/teachers/classes/{classroom}/modules', [TeacherClassroomController::class, 'assignModule'])->name('teachers.classes.modules.assign');
    Route::delete('/teachers/classes/{classroom}/modules/{module}', [TeacherClassroomController::class, 'removeModule'])->name('teachers.classes.modules.remove');
    Route::delete('/teachers/classes/{classroom}/students/{student}', [TeacherClassroomController::class, 'removeStudent'])->name('teachers.classes.students.remove');
    Route::get('/teachers/grading', [TeacherGradingController::class, 'index'])->name('teachers.grading.index');
    Route::post('/teachers/grading/attempts/{attempt}', [TeacherGradingController::class, 'grade'])->name('teachers.grading.grade');

    Route::get('/teachers/create/module', [TeacherModuleController::class, 'create'])->name('teachers.modules.create');
    Route::get('/teachers/create/Module', fn () => redirect()->route('teachers.modules.create'));
    Route::get('/teachers/modules/{module:slug}', [TeacherModuleController::class, 'show'])->name('teachers.modules.show');
    Route::post('/teachers/modules', [TeacherModuleController::class, 'store'])->name('teachers.modules.store');
    Route::match(['patch', 'post'], '/teachers/modules/{module:slug}/status', [TeacherModuleController::class, 'updateStatus'])->name('teachers.modules.status.update');

    Route::get('/teachers/modules/{module:slug}/lectures/create', [TeacherLectureController::class, 'create'])->name('teachers.lectures.create');
    Route::post('/teachers/modules/{module:slug}/lectures', [TeacherLectureController::class, 'store'])->name('teachers.lectures.store');
    Route::get('/teachers/modules/{module:slug}/lectures/{lecture:slug}/contents/create', [TeacherLectureController::class, 'createContent'])->name('teachers.lectures.contents.create');
    Route::post('/teachers/modules/{module:slug}/lectures/{lecture:slug}/contents', [TeacherLectureController::class, 'storeContent'])->name('teachers.lectures.contents.store');
    Route::post('/teachers/modules/{module:slug}/lectures/{lecture:slug}/contents/{content}', [TeacherLectureController::class, 'updateContent'])->name('teachers.lectures.contents.update');
    Route::delete('/teachers/modules/{module:slug}/lectures/{lecture:slug}/contents/{content}', [TeacherLectureController::class, 'destroyContent'])->name('teachers.lectures.contents.destroy');

    Route::get('/teachers/modules/{module:slug}/quizzes/studio', [TeacherModuleController::class, 'quizStudio'])->name('teachers.quizzes.studio');
    Route::delete('/teachers/modules/{module:slug}/quizzes/{quiz}', [TeacherModuleController::class, 'destroyQuiz'])->name('teachers.quizzes.destroy');

    Route::get('/teachers/modules/{module:slug}/quizzes/{quiz:slug}/results', [TeacherQuizResultController::class, 'show'])->name('teachers.quizzes.results');
    Route::post('/teachers/modules/{module:slug}/quizzes/{quiz:slug}/results/grade/{attempt}', [TeacherQuizResultController::class, 'grade'])->name('teachers.quizzes.results.grade');

    Route::get('/teachers/modules/{module:slug}/quizzes/multiple-choice/create', [TeacherMultipleChoiceQuizController::class, 'create'])->name('teachers.quizzes.multiple-choice.create');
    Route::post('/teachers/modules/{module:slug}/quizzes/multiple-choice', [TeacherMultipleChoiceQuizController::class, 'store'])->name('teachers.quizzes.multiple-choice.store');
    Route::get('/teachers/modules/{module:slug}/quizzes/multiple-choice/{quiz:slug}', [TeacherQuizResultController::class, 'show'])->name('teachers.quizzes.multiple-choice.show');

    Route::get('/teachers/modules/{module:slug}/quizzes/color-the-conflict/create', [TeacherColorConflictQuizController::class, 'create'])->name('teachers.quizzes.color-the-conflict.create');
    Route::post('/teachers/modules/{module:slug}/quizzes/color-the-conflict', [TeacherColorConflictQuizController::class, 'store'])->name('teachers.quizzes.color-the-conflict.store');

    Route::get('/teachers/modules/{module:slug}/quizzes/the-conflict-map/create', [TeacherConflictMapQuizController::class, 'create'])->name('teachers.quizzes.the-conflict-map.create');
    Route::post('/teachers/modules/{module:slug}/quizzes/the-conflict-map', [TeacherConflictMapQuizController::class, 'store'])->name('teachers.quizzes.the-conflict-map.store');

    Route::get('/teachers/modules/{module:slug}/quizzes/matching-type/create', [TeacherMatchingTypeQuizController::class, 'create'])->name('teachers.quizzes.matching-type.create');
    Route::post('/teachers/modules/{module:slug}/quizzes/matching-type', [TeacherMatchingTypeQuizController::class, 'store'])->name('teachers.quizzes.matching-type.store');

    Route::get('/teachers/modules/{module:slug}/quizzes/evaluation/create', [TeacherEvaluationQuizController::class, 'create'])->name('teachers.quizzes.evaluation.create');
    Route::post('/teachers/modules/{module:slug}/quizzes/evaluation', [TeacherEvaluationQuizController::class, 'store'])->name('teachers.quizzes.evaluation.store');

    Route::get('/teachers/modules/{module:slug}/quizzes/reflection/create', [TeacherReflectionQuizController::class, 'create'])->name('teachers.quizzes.reflection.create');
    Route::post('/teachers/modules/{module:slug}/quizzes/reflection', [TeacherReflectionQuizController::class, 'store'])->name('teachers.quizzes.reflection.store');

    Route::prefix('teachers/ai')->name('teachers.ai.')->group(function () {
        Route::get('/status', [TeacherAiAssistantController::class, 'status'])->name('status');
        Route::post('/word-meaning', [TeacherAiAssistantController::class, 'wordMeaning'])->name('word-meaning');
        Route::post('/lecture-assist', [TeacherAiAssistantController::class, 'lectureAssist'])->name('lecture-assist');
        Route::post('/module-assist', [TeacherAiAssistantController::class, 'moduleAssist'])->name('module-assist');
        Route::post('/quiz-assist', [TeacherAiAssistantController::class, 'quizAssist'])->name('quiz-assist');
    });

    Route::get('/students/welcome', [StudentDashboardController::class, 'welcome'])->name('students.welcome');
    Route::get('/students/dashboard', [StudentDashboardController::class, 'index'])->name('students.dashboard');
    Route::get('/students/Dashboard', fn () => redirect()->route('students.dashboard'));
    Route::post('/students/modules/join', [StudentDashboardController::class, 'joinModule'])->name('students.modules.join');
    Route::get('/students/progress', [StudentDashboardController::class, 'progress'])->name('students.progress');
    Route::get('/students/Progress', fn () => redirect()->route('students.progress'));
    Route::get('/students/ranking', [StudentDashboardController::class, 'ranking'])->name('students.ranking');
    Route::get('/students/Ranking', fn () => redirect()->route('students.ranking'));
    Route::get('/students/settings', [StudentDashboardController::class, 'settings'])->name('students.settings');
    Route::get('/students/Settings', fn () => redirect()->route('students.settings'));
    Route::patch('/students/settings/theme', [StudentDashboardController::class, 'updateTheme'])->name('students.settings.theme');
    Route::get('/students/modules/{module:slug}', [StudentDashboardController::class, 'progress'])->name('students.modules.show');
    Route::get('/students/modules/{module:slug}/lectures/{lecture:slug}', [StudentDashboardController::class, 'showLecture'])->name('students.lectures.show');
    Route::post('/students/modules/{module:slug}/lectures/{lecture:slug}/complete', [StudentDashboardController::class, 'completeLecture'])->name('students.lectures.complete');
    Route::get('/students/lecture-reader', [StudentDashboardController::class, 'showLecture'])->name('students.lectures.reader');
    Route::get('/students/modules/{module:slug}/quizzes/{quiz:slug}', [StudentDashboardController::class, 'showQuiz'])->name('students.quizzes.show');
    Route::post('/students/modules/{module:slug}/quizzes/{quiz:slug}/submit', [StudentQuizController::class, 'submit'])->name('students.quizzes.submit');
    Route::get('/students/quiz-arena', [StudentDashboardController::class, 'showQuiz'])->name('students.quizzes.arena');
});
