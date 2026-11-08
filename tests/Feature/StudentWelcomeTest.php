<?php

use App\Enums\ModuleTheme;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('student is redirected to the welcome page after login', function () {
    User::factory()->student()->create([
        'email' => 'eljames@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->post('/login', [
        'username' => 'eljames@example.com',
        'password' => 'secret123',
    ]);

    $response->assertRedirect(route('students.welcome'));
});

test('welcome page greets the student by name', function () {
    $student = User::factory()->student()->create(['name' => 'Eljames Benitez']);

    $response = $this->actingAs($student)->get(route('students.welcome'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Welcome')
            ->where('user.name', 'Eljames Benitez')
            ->where('user.role', 'student')
        );
});

test('teacher visiting the student welcome page is redirected to teacher dashboard', function () {
    $teacher = User::factory()->teacher()->create();

    $response = $this->actingAs($teacher)->get(route('students.welcome'));

    $response->assertRedirect(route('teachers.dashboard'));
});

test('guest visiting the student welcome page is redirected to login', function () {
    $response = $this->get(route('students.welcome'));

    $response->assertRedirect(route('login'));
});

test('student dashboard returns modules list for the student', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create(['name' => 'Maya Explorer']);

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Whispering Forest',
        'theme' => ModuleTheme::Forest,
        'xp_bonus' => 300,
    ]);

    $response = $this->actingAs($student)->get(route('students.dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Dashboard')
            ->where('user.name', 'Maya Explorer')
            ->has('modules', 1)
            ->where('modules.0.id', $module->id)
            ->where('modules.0.title', 'Whispering Forest')
            ->where('modules.0.theme', 'forest')
            ->where('modules.0.xp_bonus', 300)
        );
});

test('student can view the module progress expedition map', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create(['name' => 'Maya Explorer']);

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Whispering Forest',
        'slug' => 'whispering-forest',
        'theme' => ModuleTheme::Forest,
        'xp_bonus' => 300,
    ]);

    $response = $this->actingAs($student)->get(route('students.modules.show', $module));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('user.name', 'Maya Explorer')
            ->where('module.id', $module->id)
            ->where('module.title', 'Whispering Forest')
            ->where('module.slug', 'whispering-forest')
        );
});
