<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
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

    Route::get('/students/dashboard', [StudentDashboardController::class, 'index'])->name('students.dashboard');
    Route::get('/students/Dashboard', fn () => redirect()->route('students.dashboard'));
});
