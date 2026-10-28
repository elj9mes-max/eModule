<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user defaults to student role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Student);
    expect($user->isStudent())->toBeTrue();
    expect($user->isTeacher())->toBeFalse();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'student',
    ]);
});

test('user can be created with teacher role using factory state', function () {
    $teacher = User::factory()->teacher()->create();

    expect($teacher->role)->toBe(UserRole::Teacher);
    expect($teacher->isTeacher())->toBeTrue();
    expect($teacher->isStudent())->toBeFalse();
    $this->assertDatabaseHas('users', [
        'id' => $teacher->id,
        'role' => 'teacher',
    ]);
});

test('user can be created with student role using factory state', function () {
    $student = User::factory()->student()->create();

    expect($student->role)->toBe(UserRole::Student);
    expect($student->isStudent())->toBeTrue();
    expect($student->isTeacher())->toBeFalse();
    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'role' => 'student',
    ]);
});
