<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('teacher login redirects to teachers dashboard', function () {
    $teacher = User::factory()->teacher()->create([
        'email' => 'teacher@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->post('/login', [
        'username' => 'teacher@example.com',
        'password' => 'secret123',
    ]);

    $this->assertAuthenticatedAs($teacher);
    $response->assertRedirect(route('teachers.dashboard'));
});

test('student login redirects to students dashboard', function () {
    $student = User::factory()->student()->create([
        'email' => 'student@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->post('/login', [
        'username' => 'student@example.com',
        'password' => 'secret123',
    ]);

    $this->assertAuthenticatedAs($student);
    $response->assertRedirect(route('students.dashboard'));
});

test('user can also authenticate using their name', function () {
    $user = User::factory()->student()->create([
        'name' => 'Alex Rivera',
        'password' => 'secret123',
    ]);

    $response = $this->post('/login', [
        'username' => 'Alex Rivera',
        'password' => 'secret123',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('students.dashboard'));
});

test('invalid credentials are rejected', function () {
    User::factory()->create([
        'email' => 'alex@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->post('/login', [
        'username' => 'alex@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('username');
});

test('student accessing teacher dashboard is redirected to student dashboard', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->get(route('teachers.dashboard'));

    $response->assertRedirect(route('students.dashboard'));
});

test('teacher accessing student dashboard is redirected to teacher dashboard', function () {
    $teacher = User::factory()->teacher()->create();

    $response = $this->actingAs($teacher)->get(route('students.dashboard'));

    $response->assertRedirect(route('teachers.dashboard'));
});

test('teacher can access students ranking page', function () {
    $teacher = User::factory()->teacher()->create();

    $response = $this->actingAs($teacher)->get(route('teachers.students'));

    $response->assertStatus(200);
});

test('student accessing students ranking page is redirected to student dashboard', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->get(route('teachers.students'));

    $response->assertRedirect(route('students.dashboard'));
});

test('authenticated user can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('signup screen can be rendered', function () {
    $response = $this->get('/signup');

    $response->assertStatus(200);
});

test('new student can register and is redirected to student dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'New Student',
        'email' => 'newstudent@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
    ]);

    $this->assertAuthenticated();
    $user = auth()->user();
    expect($user->isStudent())->toBeTrue();
    expect($user->email)->toBe('newstudent@example.com');
    $response->assertRedirect(route('students.dashboard'));
});

test('new teacher can register and is redirected to teacher dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'New Teacher',
        'email' => 'newteacher@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'teacher',
    ]);

    $this->assertAuthenticated();
    $user = auth()->user();
    expect($user->isTeacher())->toBeTrue();
    expect($user->email)->toBe('newteacher@example.com');
    $response->assertRedirect(route('teachers.dashboard'));
});
