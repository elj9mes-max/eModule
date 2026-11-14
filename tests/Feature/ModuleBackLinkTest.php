<?php

use App\Models\Classroom;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->teacher = User::factory()->teacher()->create();
    $this->student = User::factory()->student()->create();
    $this->module = Module::factory()->for($this->teacher, 'teacher')->create();
});

test('map back button returns to the classroom the module was opened from', function (): void {
    $firstRoom = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'Sunflower Readers']);
    $secondRoom = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'Maple Readers']);

    foreach ([$firstRoom, $secondRoom] as $room) {
        $room->assignModule($this->module->id);
        $room->enrollStudent($this->student->id);
    }

    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug, 'from' => $secondRoom->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('students.classes.show', $secondRoom->id))
            ->where('backLink.label', 'Maple Readers')
        );
});

test('map back button ignores a classroom hint the student does not belong to', function (): void {
    $myRoom = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'My Room']);
    $myRoom->assignModule($this->module->id);
    $myRoom->enrollStudent($this->student->id);

    $otherRoom = Classroom::factory()->create(['user_id' => $this->teacher->id]);
    $otherRoom->assignModule($this->module->id);

    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug, 'from' => $otherRoom->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('students.classes.show', $myRoom->id))
            ->where('backLink.label', 'My Room')
        );
});

test('map back button falls back to the classroom that contains the module', function (): void {
    $room = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'Oak Room']);
    $room->assignModule($this->module->id);
    $room->enrollStudent($this->student->id);

    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('students.classes.show', $room->id))
        );
});

test('map back button returns to code adventures for code-joined modules', function (): void {
    ModuleEnrollment::create([
        'module_id' => $this->module->id,
        'user_id' => $this->student->id,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug, 'from' => 'code']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('students.classes.code-adventures'))
            ->where('backLink.label', 'Code Adventures')
        );
});

test('map back button defaults to all classrooms without any membership', function (): void {
    $this->actingAs($this->student)
        ->get(route('students.modules.show', ['module' => $this->module->slug, 'from' => '999']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('students.classes.index'))
            ->where('backLink.label', 'All Classrooms')
        );
});
