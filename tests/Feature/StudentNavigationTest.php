<?php

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
        );
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
