<?php

use App\Enums\ModuleStatus;
use App\Models\Module;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner teacher can toggle module from draft to published', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Draft,
        'published_at' => null,
    ]);

    $response = $this->actingAs($teacher)
        ->patch("/teachers/modules/{$module->slug}/status");

    $response->assertRedirect();
    $module->refresh();

    expect($module->status)->toBe(ModuleStatus::Published)
        ->and($module->published_at)->not->toBeNull();
});

test('owner teacher can toggle module from published to draft', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Published,
        'published_at' => now(),
    ]);

    $response = $this->actingAs($teacher)
        ->patch("/teachers/modules/{$module->slug}/status");

    $response->assertRedirect();
    $module->refresh();

    expect($module->status)->toBe(ModuleStatus::Draft)
        ->and($module->published_at)->toBeNull();
});

test('owner teacher can explicitly specify target status', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Draft,
    ]);

    $response = $this->actingAs($teacher)
        ->patch("/teachers/modules/{$module->slug}/status", [
            'status' => 'published',
        ]);

    $response->assertRedirect();
    $module->refresh();

    expect($module->status)->toBe(ModuleStatus::Published);

    $this->actingAs($teacher)
        ->patch("/teachers/modules/{$module->slug}/status", [
            'status' => 'draft',
        ]);

    $module->refresh();
    expect($module->status)->toBe(ModuleStatus::Draft);
});

test('json response is returned when client requests json without inertia header', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Draft,
    ]);

    $response = $this->actingAs($teacher)
        ->json('PATCH', "/teachers/modules/{$module->slug}/status", [
            'status' => 'published',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'published',
        ]);

    $module->refresh();
    expect($module->status)->toBe(ModuleStatus::Published);
});

test('another teacher cannot modify module status', function () {
    $owner = User::factory()->teacher()->create();
    $intruder = User::factory()->teacher()->create();
    $module = Module::factory()->for($owner, 'teacher')->create([
        'status' => ModuleStatus::Draft,
    ]);

    $response = $this->actingAs($intruder)
        ->patch("/teachers/modules/{$module->slug}/status");

    $response->assertForbidden();
    $module->refresh();
    expect($module->status)->toBe(ModuleStatus::Draft);
});

test('student cannot modify module status', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Draft,
    ]);

    $response = $this->actingAs($student)
        ->patch("/teachers/modules/{$module->slug}/status");

    $response->assertForbidden();
    $module->refresh();
    expect($module->status)->toBe(ModuleStatus::Draft);
});

test('invalid status value fails validation with 422', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Draft,
    ]);

    $response = $this->actingAs($teacher)
        ->json('PATCH', "/teachers/modules/{$module->slug}/status", [
            'status' => 'invalid_status_xyz',
        ]);

    $response->assertUnprocessable();
    $module->refresh();
    expect($module->status)->toBe(ModuleStatus::Draft);
});

test('toggling module status invalidates cache so fresh data is returned', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Draft,
    ]);

    /** @var EModuleCacheService $cache */
    $cache = app(EModuleCacheService::class);

    // Warm cache
    $initialModules = $cache->getTeacherModules($teacher->id);
    expect($initialModules[0]['status'])->toBe('draft');

    // Toggle status
    $this->actingAs($teacher)
        ->patch("/teachers/modules/{$module->slug}/status");

    // Fresh query via cache should reflect new status
    $updatedModules = $cache->getTeacherModules($teacher->id);
    expect($updatedModules[0]['status'])->toBe('published');
});
