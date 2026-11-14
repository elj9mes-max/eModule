<?php

use App\Enums\ModuleStatus;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\StudentProfile;
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

    $response->assertRedirect(route('students.classes.show', $classroom));
    $response->assertSessionHas('success');

    // Enrolled in classroom
    $this->assertDatabaseHas('classroom_students', [
        'classroom_id' => $classroom->id,
        'user_id' => $student->id,
    ]);

    // Classroom modules come from membership, not from copied module enrollments
    $this->assertDatabaseCount('module_enrollments', 0);

    $this->actingAs($student)->get(route('students.classes.show', $classroom))
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/ClassroomModules')
            ->where('classroom.id', $classroom->id)
            ->has('modules', 2)
            ->where('modules', fn ($modules) => collect($modules)->pluck('id')->sort()->values()->all() === collect([$module1->id, $module2->id])->sort()->values()->all())
        );
});

test('student classrooms page lists only the classrooms the student joined', function () {
    $teacher = User::factory()->teacher()->create();
    $classroomA = Classroom::factory()->create(['user_id' => $teacher->id, 'class_code' => '111111']);
    $classroomB = Classroom::factory()->create(['user_id' => $teacher->id, 'class_code' => '222222']);

    $moduleInA = Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    $moduleInB = Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    $classroomA->assignModule($moduleInA);
    $classroomB->assignModule($moduleInB);

    $classmateInA = User::factory()->student()->create();
    $classroomA->enrollStudent($classmateInA);

    $student = User::factory()->student()->create();
    $this->actingAs($student)->post(route('students.modules.join'), ['join_code' => '222222']);

    $this->actingAs($student)->get(route('students.classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Classrooms')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $classroomB->id)
            ->where('classrooms.0.modules_count', 1)
        );

    $this->actingAs($student)->get(route('students.classes.show', $classroomB))
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules', 1)
            ->where('modules.0.id', $moduleInB->id)
        );
});

test('student cannot open a classroom they have not joined', function () {
    $teacher = User::factory()->teacher()->create();
    $otherRoom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $otherRoom->assignModule(Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]));

    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('students.classes.show', $otherRoom))
        ->assertRedirect(route('students.classes.index'));
});

test('student classroom page hides draft modules even when assigned to the classroom', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $classroom->assignModule(Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Draft]));

    $student = User::factory()->student()->create();
    $classroom->enrollStudent($student);

    $this->actingAs($student)->get(route('students.classes.show', $classroom))
        ->assertInertia(fn (Assert $page) => $page->has('modules', 0));

    $this->actingAs($student)->get(route('students.classes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('classrooms.0.modules_count', 0));
});

test('removing a module from a classroom hides it from the classroom students', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $module = Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    $classroom->assignModule($module);

    $student = User::factory()->student()->create();
    $classroom->enrollStudent($student);

    $this->actingAs($student)->get(route('students.classes.show', $classroom))
        ->assertInertia(fn (Assert $page) => $page->has('modules', 1));

    $this->actingAs($teacher)->delete(route('teachers.classes.modules.remove', [$classroom, $module]))->assertRedirect();

    $this->actingAs($student)->get(route('students.classes.show', $classroom))
        ->assertInertia(fn (Assert $page) => $page->has('modules', 0));
});

test('removing a student from a classroom hides that classroom modules but keeps direct code joins', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $classroomModule = Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    $classroom->assignModule($classroomModule);
    $directModule = Module::factory()->create([
        'user_id' => $teacher->id,
        'status' => ModuleStatus::Published,
        'join_code' => '737373',
    ]);

    $student = User::factory()->student()->create();
    $classroom->enrollStudent($student);
    $this->actingAs($student)->post(route('students.modules.join'), ['join_code' => '737373']);

    $this->actingAs($student)->get(route('students.classes.show', $classroom))
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules', 1)
            ->where('modules.0.id', $classroomModule->id)
        );

    $this->actingAs($teacher)->delete(route('teachers.classes.students.remove', [$classroom, $student]))->assertRedirect();

    $this->actingAs($student)->get(route('students.classes.show', $classroom))
        ->assertRedirect(route('students.classes.index'));

    $this->actingAs($student)->get(route('students.classes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classrooms', 0)
            ->where('direct_modules_count', 1)
        );

    $this->actingAs($student)->get(route('students.classes.code-adventures'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules', 1)
            ->where('modules.0.id', $directModule->id)
        );
});

test('student in two classrooms sees each classroom modules separately', function () {
    $teacher = User::factory()->teacher()->create();
    $firstRoom = Classroom::factory()->create(['user_id' => $teacher->id, 'class_code' => '313131']);
    $secondRoom = Classroom::factory()->create(['user_id' => $teacher->id, 'class_code' => '424242']);
    $firstRoomModule = Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    $secondRoomModule = Module::factory()->create(['user_id' => $teacher->id, 'status' => ModuleStatus::Published]);
    $firstRoom->assignModule($firstRoomModule);
    $secondRoom->assignModule($secondRoomModule);

    $student = User::factory()->student()->create();
    $this->actingAs($student)->post(route('students.modules.join'), ['join_code' => '313131']);
    $this->travel(1)->minutes();

    $this->actingAs($student)
        ->post(route('students.modules.join'), ['join_code' => '424242'])
        ->assertRedirect(route('students.classes.show', $secondRoom));

    // Newest classroom first on the classrooms page
    $this->actingAs($student)->get(route('students.classes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classrooms', 2)
            ->where('classrooms.0.id', $secondRoom->id)
            ->where('classrooms.1.id', $firstRoom->id)
        );

    // Each classroom page shows only its own modules, never merged
    $this->actingAs($student)->get(route('students.classes.show', $secondRoom))
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules', 1)
            ->where('modules.0.id', $secondRoomModule->id)
        );

    $this->actingAs($student)->get(route('students.classes.show', $firstRoom))
        ->assertInertia(fn (Assert $page) => $page
            ->has('modules', 1)
            ->where('modules.0.id', $firstRoomModule->id)
        );
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

test('student ranking only reveals the top five and hides the student\'s own low rank', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);

    foreach (range(1, 6) as $position) {
        $classmate = User::factory()->student()->create();
        $classroom->enrollStudent($classmate->id);
        StudentProfile::updateOrCreate(['user_id' => $classmate->id], ['total_xp' => 1000 - $position * 100]);
    }

    $student = User::factory()->student()->create();
    $classroom->enrollStudent($student->id);
    StudentProfile::updateOrCreate(['user_id' => $student->id], ['total_xp' => 50]);

    $this->actingAs($student)
        ->get(route('students.ranking', ['class_id' => $classroom->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('classmates', 5)
            ->missing('classmates.0.email')
            ->where('classmatesCount', 7)
            ->where('me.in_top', false)
            ->where('me.rank', null)
            ->where('me.xp', 50)
            ->where('me.xp_to_top', 451)
            ->missing('classrooms')
        );
});

test('student cannot view the leaderboard of a classroom they do not belong to', function () {
    $teacher = User::factory()->teacher()->create();
    $myRoom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $otherRoom = Classroom::factory()->create(['user_id' => $teacher->id]);

    $student = User::factory()->student()->create();
    $stranger = User::factory()->student()->create();

    $myRoom->enrollStudent($student->id);
    $otherRoom->enrollStudent($stranger->id);

    $this->actingAs($student)
        ->get(route('students.ranking', ['class_id' => $otherRoom->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Ranking')
            ->where('active_classroom.id', $myRoom->id)
            ->has('classmates', 1)
            ->where('classmates.0.id', $student->id)
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

test('create module page receives the classroom the teacher clicked add module from', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id, 'name' => 'Grade 3 - Alpha']);

    $this->actingAs($teacher)
        ->get(route('teachers.modules.create', ['class_id' => $classroom->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/Module')
            ->where('classroom.id', $classroom->id)
            ->where('classroom.name', 'Grade 3 - Alpha')
            ->missing('classrooms')
        );
});

test('create module page ignores a classroom the teacher does not own', function () {
    $teacher = User::factory()->teacher()->create();
    $foreignClassroom = Classroom::factory()->create();

    $this->actingAs($teacher)
        ->get(route('teachers.modules.create', ['class_id' => $foreignClassroom->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('classroom', null));
});

test('creating a module assigns it to the classroom it was added from', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create(['user_id' => $teacher->id]);
    $otherClassroom = Classroom::factory()->create(['user_id' => $teacher->id]);

    $response = $this->actingAs($teacher)->post(route('teachers.modules.store'), [
        'title' => 'Galactic Reading Pioneers',
        'theme' => 'cosmic',
        'icon' => '🚀',
        'xp_bonus' => 500,
        'status' => 'published',
        'classroom_id' => $classroom->id,
    ]);

    $response->assertRedirect(route('teachers.dashboard', ['class_id' => $classroom->id]));

    $module = Module::where('title', 'Galactic Reading Pioneers')->firstOrFail();

    $this->assertDatabaseHas('classroom_modules', [
        'classroom_id' => $classroom->id,
        'module_id' => $module->id,
    ]);

    $this->assertDatabaseMissing('classroom_modules', [
        'classroom_id' => $otherClassroom->id,
        'module_id' => $module->id,
    ]);
});

test('creating a module never assigns it to another teachers classroom', function () {
    $teacher = User::factory()->teacher()->create();
    $foreignClassroom = Classroom::factory()->create();

    $this->actingAs($teacher)->post(route('teachers.modules.store'), [
        'title' => 'Sneaky Module',
        'theme' => 'cosmic',
        'icon' => '🚀',
        'xp_bonus' => 100,
        'status' => 'draft',
        'classroom_id' => $foreignClassroom->id,
    ])->assertRedirect(route('teachers.dashboard'));

    $this->assertDatabaseMissing('classroom_modules', [
        'classroom_id' => $foreignClassroom->id,
    ]);
});
