<?php

use App\Enums\ModuleStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('newly registered student sees no classrooms and no code adventures if not enrolled', function () {
    $teacher = User::factory()->teacher()->create();
    Module::factory()->count(3)->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
    ]);

    $newStudent = User::factory()->student()->create();

    $this->actingAs($newStudent)->get(route('students.dashboard'))
        ->assertRedirect(route('students.classes.index'));

    $this->actingAs($newStudent)->get(route('students.classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Classrooms')
            ->has('classrooms', 0)
            ->where('direct_modules_count', 0)
        );
});

test('student can join published module with valid 6 digit code', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
        'join_code' => '482910',
    ]);

    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '482910',
    ]);

    $response->assertRedirect(route('students.classes.code-adventures'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('module_enrollments', [
        'user_id' => $student->id,
        'module_id' => $module->id,
    ]);
});

test('module joined by code immediately appears in code adventures, not in a classroom', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
        'join_code' => '392817',
    ]);

    $student = User::factory()->student()->create();

    $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '392817',
    ]);

    $this->actingAs($student)->get(route('students.classes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classrooms', 0)
            ->where('direct_modules_count', 1)
        );

    $this->actingAs($student)->get(route('students.classes.code-adventures'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/ClassroomModules')
            ->has('modules', 1)
            ->where('modules.0.id', $module->id)
        );
});

test('student cannot join module with invalid code format', function (string $invalidCode) {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => $invalidCode,
    ]);

    $response->assertSessionHasErrors('join_code');
})->with([
    'too short' => '12345',
    'too long' => '1234567',
    'alphanumeric' => 'ABC123',
    'symbols' => '12-345',
    'empty' => '',
]);

test('student gets error when code does not match any published module', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '999999',
    ]);

    $response->assertSessionHasErrors('join_code');

    // JSON response gets 404
    $jsonResponse = $this->actingAs($student)->postJson(route('students.modules.join'), [
        'join_code' => '999999',
    ]);
    $jsonResponse->assertNotFound();
});

test('student cannot join draft module even if code matches', function () {
    $teacher = User::factory()->teacher()->create();
    Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Draft,
        'join_code' => '777888',
    ]);

    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '777888',
    ]);

    $response->assertSessionHasErrors('join_code');
});

test('student cannot join the same module twice', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
        'join_code' => '555666',
    ]);

    $student = User::factory()->student()->create();

    ModuleEnrollment::create([
        'user_id' => $student->id,
        'module_id' => $module->id,
    ]);

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '555666',
    ]);

    $response->assertSessionHasErrors(['join_code' => 'You have already joined this module.']);

    // JSON call gets 422
    $jsonResponse = $this->actingAs($student)->postJson(route('students.modules.join'), [
        'join_code' => '555666',
    ]);
    $jsonResponse->assertStatus(422);
});

test('student cannot join module when maximum capacity is reached', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
        'join_code' => '111222',
        'max_students' => 2,
    ]);

    $existingStudent1 = User::factory()->student()->create();
    $existingStudent2 = User::factory()->student()->create();

    ModuleEnrollment::create(['user_id' => $existingStudent1->id, 'module_id' => $module->id]);
    ModuleEnrollment::create(['user_id' => $existingStudent2->id, 'module_id' => $module->id]);

    $newStudent = User::factory()->student()->create();

    $response = $this->actingAs($newStudent)->post(route('students.modules.join'), [
        'join_code' => '111222',
    ]);

    $response->assertSessionHasErrors(['join_code' => 'This reading quest is already at full capacity (2 students).']);

    // JSON call gets 422
    $jsonResponse = $this->actingAs($newStudent)->postJson(route('students.modules.join'), [
        'join_code' => '111222',
    ]);
    $jsonResponse->assertStatus(422);
});

test('teacher cannot use student module join endpoint', function () {
    $teacher = User::factory()->teacher()->create();

    $response = $this->actingAs($teacher)->post(route('students.modules.join'), [
        'join_code' => '123456',
    ]);

    $response->assertForbidden();
});
