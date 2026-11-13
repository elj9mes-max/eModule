<?php

use App\Enums\ModuleStatus;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('teacher can view classrooms index page', function () {
    $teacher = User::factory()->teacher()->create();
    Classroom::factory()->count(2)->create(['user_id' => $teacher->id]);

    $response = $this->actingAs($teacher)->get(route('teachers.classes.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Classes')
            ->has('classrooms', 2)
        );
});

test('teacher can create a new classroom with auto-generated 6-digit code', function () {
    $teacher = User::factory()->teacher()->create();

    $response = $this->actingAs($teacher)->post(route('teachers.classes.store'), [
        'name' => 'Grade 3 - Emerald',
        'grade_level' => 'Grade 3',
        'theme' => 'forest',
        'icon' => '💎',
        'description' => 'Morning reading cohort',
        'max_students' => 35,
    ]);

    $response->assertRedirect(route('teachers.classes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('classrooms', [
        'user_id' => $teacher->id,
        'name' => 'Grade 3 - Emerald',
        'grade_level' => 'Grade 3',
        'theme' => 'forest',
    ]);

    $classroom = Classroom::where('name', 'Grade 3 - Emerald')->first();
    expect($classroom)->not->toBeNull();
    expect($classroom->class_code)->toMatch('/^\d{6}$/');
});

test('teacher can assign and remove a module to/from their classroom', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $module = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
    ]);

    // Assign module
    $assignResponse = $this->actingAs($teacher)->post(route('teachers.classes.modules.assign', $classroom), [
        'module_id' => $module->id,
    ]);

    $assignResponse->assertRedirect();
    $this->assertDatabaseHas('classroom_modules', [
        'classroom_id' => $classroom->id,
        'module_id' => $module->id,
    ]);

    // Remove module
    $removeResponse = $this->actingAs($teacher)->delete(route('teachers.classes.modules.remove', [$classroom, $module]));
    $removeResponse->assertRedirect();
    $this->assertDatabaseMissing('classroom_modules', [
        'classroom_id' => $classroom->id,
        'module_id' => $module->id,
    ]);
});

test('teacher cannot modify another teachers classroom', function () {
    $teacher1 = User::factory()->teacher()->create();
    $teacher2 = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher1->id]);
    $module = Module::factory()->create(['user_id' => $teacher2->id]);

    $response = $this->actingAs($teacher2)->post(route('teachers.classes.modules.assign', $classroom), [
        'module_id' => $module->id,
    ]);

    $response->assertForbidden();
});

test('student joining via classroom 6-digit code gets enrolled in room and all room modules', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create([
        'user_id' => $teacher->id,
        'class_code' => '654321',
    ]);

    $module1 = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
    ]);
    $module2 = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
    ]);

    $classroom->assignModule($module1->id);
    $classroom->assignModule($module2->id);

    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '654321',
    ]);

    $response->assertRedirect(route('students.dashboard'));
    $response->assertSessionHas('success');

    // Enrolled in classroom
    $this->assertDatabaseHas('classroom_students', [
        'classroom_id' => $classroom->id,
        'user_id' => $student->id,
    ]);

    // Auto-enrolled in all classroom modules
    $this->assertDatabaseHas('module_enrollments', [
        'user_id' => $student->id,
        'module_id' => $module1->id,
    ]);
    $this->assertDatabaseHas('module_enrollments', [
        'user_id' => $student->id,
        'module_id' => $module2->id,
    ]);
});

test('student cannot join the same classroom twice', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create([
        'user_id' => $teacher->id,
        'class_code' => '987123',
    ]);

    $student = User::factory()->student()->create();
    $classroom->enrollStudent($student->id);

    $response = $this->actingAs($student)->post(route('students.modules.join'), [
        'join_code' => '987123',
    ]);

    $response->assertSessionHasErrors('join_code');
});

test('teacher students view strictly displays students enrolled in their classrooms', function () {
    $teacher1 = User::factory()->teacher()->create();
    $teacher2 = User::factory()->teacher()->create();

    $class1 = Classroom::factory()->create(['user_id' => $teacher1->id]);
    $class2 = Classroom::factory()->create(['user_id' => $teacher2->id]);

    $student1 = User::factory()->student()->create(['name' => 'Alice In Class 1']);
    $student2 = User::factory()->student()->create(['name' => 'Bob In Class 2']);

    $class1->enrollStudent($student1->id);
    $class2->enrollStudent($student2->id);

    // Teacher 1 should only see student 1
    $response = $this->actingAs($teacher1)->get(route('teachers.students'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Students')
            ->has('students', 1)
            ->where('students.0.id', $student1->id)
            ->where('students.0.name', 'Alice In Class 1')
        );
});

test('student ranking page isolates leaderboard to classroom members', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);

    $studentA = User::factory()->student()->create(['name' => 'Classmate A']);
    $studentB = User::factory()->student()->create(['name' => 'Classmate B']);
    $outsider = User::factory()->student()->create(['name' => 'Outsider Student']);

    $classroom->enrollStudent($studentA->id);
    $classroom->enrollStudent($studentB->id);

    // outsider is not enrolled in this classroom

    $response = $this->actingAs($studentA)->get(route('students.ranking', ['class_id' => $classroom->id]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Ranking')
            ->has('classmates', 2)
            ->where('active_classroom.id', $classroom->id)
        );
});

test('teacher can remove a student from classroom', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $student = User::factory()->student()->create();

    $classroom->enrollStudent($student->id);

    $response = $this->actingAs($teacher)->delete(route('teachers.classes.students.remove', [$classroom, $student]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('classroom_students', [
        'classroom_id' => $classroom->id,
        'user_id' => $student->id,
    ]);
});

test('teacher dashboard filters modules by classroom when class_id is passed', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom1 = Classroom::factory()->create(['user_id' => $teacher->id, 'name' => 'Ruby Room']);
    $classroom2 = Classroom::factory()->create(['user_id' => $teacher->id, 'name' => 'Sapphire Room']);

    $module1 = Module::factory()->create(['user_id' => $teacher->id, 'title' => 'Ruby Adventure']);
    $module2 = Module::factory()->create(['user_id' => $teacher->id, 'title' => 'Sapphire Quest']);

    $classroom1->assignModule($module1);
    $classroom2->assignModule($module2);

    // 1. Without class_id filter -> returns both modules
    $responseAll = $this->actingAs($teacher)->get(route('teachers.dashboard'));
    $responseAll->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Dashboard')
            ->has('modules', 2)
            ->where('selected_classroom_id', null)
        );

    // 2. Filtered by classroom1 -> returns only module1
    $responseClass1 = $this->actingAs($teacher)->get(route('teachers.dashboard', ['class_id' => $classroom1->id]));
    $responseClass1->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Dashboard')
            ->has('modules', 1)
            ->where('modules.0.id', $module1->id)
            ->where('selected_classroom_id', $classroom1->id)
            ->where('selected_classroom.name', 'Ruby Room')
        );
});

test('teacher can assign classrooms directly while creating a module', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom1 = Classroom::factory()->create(['user_id' => $teacher->id, 'name' => 'Grade 3 - Alpha']);
    $classroom2 = Classroom::factory()->create(['user_id' => $teacher->id, 'name' => 'Grade 3 - Beta']);

    $response = $this->actingAs($teacher)->post(route('teachers.modules.store'), [
        'title' => 'Galactic Reading Pioneers',
        'grade_level' => 'Grade 3',
        'theme' => 'cosmic',
        'icon' => '🚀',
        'xp_bonus' => 500,
        'status' => 'published',
        'classroom_ids' => [$classroom1->id, $classroom2->id],
    ]);

    $response->assertRedirect(route('teachers.dashboard'));

    $module = Module::where('title', 'Galactic Reading Pioneers')->first();
    expect($module)->not->toBeNull();

    $this->assertDatabaseHas('classroom_modules', [
        'classroom_id' => $classroom1->id,
        'module_id' => $module->id,
    ]);

    $this->assertDatabaseHas('classroom_modules', [
        'classroom_id' => $classroom2->id,
        'module_id' => $module->id,
    ]);
});
