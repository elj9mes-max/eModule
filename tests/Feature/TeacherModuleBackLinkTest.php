<?php

use App\Models\Classroom;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->teacher = User::factory()->teacher()->create();
    $this->classroom = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'Ruby Room']);
    $this->module = Module::factory()->create(['user_id' => $this->teacher->id]);
    $this->classroom->assignModule($this->module);
});

test('module back button returns to the classroom-filtered modules list it was opened from', function (): void {
    $this->actingAs($this->teacher)->get(route('teachers.dashboard', ['class_id' => $this->classroom->id]))->assertOk();

    $this->actingAs($this->teacher)
        ->get(route('teachers.modules.show', $this->module))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/view/Module')
            ->where('backLink.url', route('teachers.dashboard', ['class_id' => $this->classroom->id]))
            ->where('backLink.label', 'Ruby Room')
        );
});

test('module back button returns to all modules after the teacher picked the unfiltered list', function (): void {
    $this->actingAs($this->teacher)->get(route('teachers.dashboard', ['class_id' => $this->classroom->id]))->assertOk();
    $this->actingAs($this->teacher)->get(route('teachers.dashboard'))->assertOk();

    $this->actingAs($this->teacher)
        ->get(route('teachers.modules.show', $this->module))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('teachers.dashboard'))
            ->where('backLink.label', 'All Modules')
        );
});

test('module back button ignores a remembered classroom the teacher does not own', function (): void {
    $otherClassroom = Classroom::factory()->create();

    $this->actingAs($this->teacher)
        ->withSession(['teacher_modules_class_id' => $otherClassroom->id])
        ->get(route('teachers.modules.show', $this->module))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.url', route('teachers.dashboard'))
        );
});

test('hover prefetches of another classroom never change where Back returns', function (): void {
    $otherRoom = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'Sapphire Room']);
    $otherRoom->assignModule($this->module);

    $this->actingAs($this->teacher)->get(route('teachers.dashboard', ['class_id' => $this->classroom->id]))->assertOk();

    // Hovering the other classroom's pill / module card prefetches pages in the background
    $this->actingAs($this->teacher)
        ->withHeaders(['Purpose' => 'prefetch'])
        ->get(route('teachers.dashboard', ['class_id' => $otherRoom->id]))
        ->assertOk();
    $this->actingAs($this->teacher)
        ->withHeaders(['Purpose' => 'prefetch'])
        ->get(route('teachers.modules.show', ['module' => $this->module, 'class_id' => $otherRoom->id]))
        ->assertOk();

    $this->actingAs($this->teacher)
        ->get(route('teachers.modules.show', $this->module))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('backLink.label', 'Ruby Room'));
});

test('a module shared by two classrooms returns to the classroom it was opened from', function (): void {
    $otherRoom = Classroom::factory()->create(['user_id' => $this->teacher->id, 'name' => 'Sapphire Room']);
    $otherRoom->assignModule($this->module);

    $this->actingAs($this->teacher)
        ->get(route('teachers.modules.show', ['module' => $this->module, 'class_id' => $this->classroom->id]))
        ->assertInertia(fn (Assert $page) => $page->where('backLink.label', 'Ruby Room'));

    $this->actingAs($this->teacher)
        ->get(route('teachers.modules.show', ['module' => $this->module, 'class_id' => $otherRoom->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('backLink.label', 'Sapphire Room')
            ->where('backLink.url', route('teachers.dashboard', ['class_id' => $otherRoom->id]))
        );

    // Coming back from a lecture/quiz editor (no class_id) keeps the last real classroom
    $this->actingAs($this->teacher)
        ->get(route('teachers.modules.show', $this->module))
        ->assertInertia(fn (Assert $page) => $page->where('backLink.label', 'Sapphire Room'));
});
