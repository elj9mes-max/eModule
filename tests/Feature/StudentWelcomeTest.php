<?php

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
