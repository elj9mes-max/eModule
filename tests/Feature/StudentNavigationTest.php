<?php

use App\Enums\PixarWorldTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('student can visit the ranking leaderboard page', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->get(route('students.ranking'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Ranking')
            ->where('user.id', $student->id)
            ->where('user.ui_theme', PixarWorldTheme::Toybox->value)
            ->has('classmates')
        );
});

test('student can visit the audio and preferences settings page', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->get(route('students.settings'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Settings')
            ->where('user.id', $student->id)
            ->where('user.ui_theme', PixarWorldTheme::Toybox->value)
            ->where('auth.user.ui_theme', PixarWorldTheme::Toybox->value)
        );
});

test('student can save their pixar color world theme and retain it across logins', function () {
    $student = User::factory()->student()->create([
        'email' => 'maya.theme@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->actingAs($student)
        ->from(route('students.settings'))
        ->patch(route('students.settings.theme'), [
            'ui_theme' => PixarWorldTheme::MemoryOrb->value,
        ]);

    $response->assertRedirect(route('students.settings'));

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'ui_theme' => PixarWorldTheme::MemoryOrb->value,
    ]);

    // Simulate logout and subsequent fresh login
    $this->post(route('logout'))->assertRedirect('/');

    $this->post('/login', [
        'username' => 'maya.theme@example.com',
        'password' => 'secret123',
    ])->assertRedirect(route('students.welcome'));

    $this->get(route('students.welcome'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Welcome')
            ->where('user.ui_theme', PixarWorldTheme::MemoryOrb->value)
            ->where('auth.user.ui_theme', PixarWorldTheme::MemoryOrb->value)
        );

    $this->get(route('students.classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Classrooms')
            ->where('user.ui_theme', PixarWorldTheme::MemoryOrb->value)
            ->where('auth.user.ui_theme', PixarWorldTheme::MemoryOrb->value)
        );

    $this->get(route('students.settings'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Settings')
            ->where('user.ui_theme', PixarWorldTheme::MemoryOrb->value)
        );
});

test('student color theme update rejects unsupported themes', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)
        ->from(route('students.settings'))
        ->patch(route('students.settings.theme'), [
            'ui_theme' => 'invalid-world',
        ]);

    $response->assertSessionHasErrors('ui_theme');

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'ui_theme' => PixarWorldTheme::Toybox->value,
    ]);
});

test('teacher and guest cannot update student color theme', function () {
    $this->patch(route('students.settings.theme'), [
        'ui_theme' => PixarWorldTheme::LucaCoast->value,
    ])->assertRedirect(route('login'));

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->patch(route('students.settings.theme'), [
            'ui_theme' => PixarWorldTheme::LucaCoast->value,
        ])
        ->assertForbidden();
});

test('teacher visiting student settings or ranking is redirected to teacher dashboard', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('students.settings'))
        ->assertRedirect(route('teachers.dashboard'));

    $this->actingAs($teacher)
        ->get(route('students.ranking'))
        ->assertRedirect(route('teachers.dashboard'));
});
