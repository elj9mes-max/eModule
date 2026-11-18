<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest cannot visit student classmates page', function () {
    $this->get(route('students.classmates'))
        ->assertRedirect(route('login'));
});

test('teacher is redirected when accessing student classmates page', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('students.classmates'))
        ->assertRedirect(route('teachers.dashboard'));
});

test('student with no classrooms sees empty list of classmates', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->get(route('students.classmates'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Classmates')
            ->where('user.id', $student->id)
            ->where('classmates', [])
            ->where('classrooms', [])
        );
});

test('student only sees classmates who share the same classroom', function () {
    $teacher = User::factory()->teacher()->create();

    $classroomA = Classroom::factory()->create([
        'user_id' => $teacher->id,
        'name' => 'Room Alpha',
        'is_active' => true,
    ]);

    $classroomB = Classroom::factory()->create([
        'user_id' => $teacher->id,
        'name' => 'Room Beta',
        'is_active' => true,
    ]);

    $studentMe = User::factory()->student()->create(['name' => 'Me Explorer']);
    $studentPeerA = User::factory()->student()->create(['name' => 'Peer Alpha']);
    $studentPeerB = User::factory()->student()->create(['name' => 'Peer Beta Only']);

    // Enroll in Classroom A
    $classroomA->students()->attach([$studentMe->id, $studentPeerA->id], ['joined_at' => now()]);

    // Enroll only studentPeerB in Classroom B
    $classroomB->students()->attach([$studentPeerB->id], ['joined_at' => now()]);

    $response = $this->actingAs($studentMe)->get(route('students.classmates'));

    $response->assertOk()
        ->assertInertia(function (Assert $page) use ($studentMe, $studentPeerA, $studentPeerB) {
            $page->component('students/Classmates')
                ->where('user.id', $studentMe->id)
                ->has('classmates', 2)
                ->where('classmates.0.id', fn ($id) => in_array($id, [$studentMe->id, $studentPeerA->id], true))
                ->where('classmates.1.id', fn ($id) => in_array($id, [$studentMe->id, $studentPeerA->id], true));

            $classmateIds = collect($page->toArray()['props']['classmates'])->pluck('id')->all();
            expect($classmateIds)->toContain($studentMe->id)
                ->toContain($studentPeerA->id)
                ->not->toContain($studentPeerB->id);
        });
});

test('student can view profile of a classmate who shares a classroom', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->create([
        'user_id' => $teacher->id,
        'is_active' => true,
    ]);

    $studentA = User::factory()->student()->create(['name' => 'Alice']);
    $studentB = User::factory()->student()->create(['name' => 'Bob']);

    $classroom->students()->attach([$studentA->id, $studentB->id], ['joined_at' => now()]);

    $response = $this->actingAs($studentA)->get(route('students.classmates.profile', $studentB));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/ClassmatesProfile')
            ->where('classmate.id', $studentB->id)
            ->where('classmate.name', 'Bob')
            ->has('classmate.shared_classrooms', 1)
        );
});

test('student cannot view profile of a student from another non-shared classroom', function () {
    $teacher = User::factory()->teacher()->create();

    $classroomA = Classroom::factory()->create(['user_id' => $teacher->id, 'is_active' => true]);
    $classroomB = Classroom::factory()->create(['user_id' => $teacher->id, 'is_active' => true]);

    $studentA = User::factory()->student()->create(['name' => 'Alice']);
    $studentStranger = User::factory()->student()->create(['name' => 'Stranger']);

    $classroomA->students()->attach([$studentA->id], ['joined_at' => now()]);
    $classroomB->students()->attach([$studentStranger->id], ['joined_at' => now()]);

    $response = $this->actingAs($studentA)->get(route('students.classmates.profile', $studentStranger));

    $response->assertForbidden();
});
