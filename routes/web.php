<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherLectureController;
use App\Http\Controllers\TeacherModuleController;
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

    Route::get('/teachers/create/module', [TeacherModuleController::class, 'create'])->name('teachers.modules.create');
    Route::get('/teachers/create/Module', fn () => redirect()->route('teachers.modules.create'));
    Route::get('/teachers/modules/{module:slug}', [TeacherModuleController::class, 'show'])->name('teachers.modules.show');
    Route::post('/teachers/modules', [TeacherModuleController::class, 'store'])->name('teachers.modules.store');

    Route::get('/teachers/modules/{module:slug}/lectures/create', [TeacherLectureController::class, 'create'])->name('teachers.lectures.create');
    Route::post('/teachers/modules/{module:slug}/lectures', [TeacherLectureController::class, 'store'])->name('teachers.lectures.store');
    Route::get('/teachers/modules/{module:slug}/lectures/{lecture:slug}/contents/create', [TeacherLectureController::class, 'createContent'])->name('teachers.lectures.contents.create');
    Route::post('/teachers/modules/{module:slug}/lectures/{lecture:slug}/contents', [TeacherLectureController::class, 'storeContent'])->name('teachers.lectures.contents.store');

    Route::get('/students/welcome', [StudentDashboardController::class, 'welcome'])->name('students.welcome');
    Route::get('/students/dashboard', [StudentDashboardController::class, 'index'])->name('students.dashboard');
    Route::get('/students/Dashboard', fn () => redirect()->route('students.dashboard'));
});
