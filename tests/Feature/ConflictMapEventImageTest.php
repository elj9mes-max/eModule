<?php

use App\Models\ConflictMapEvent;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');

    $this->teacher = User::factory()->teacher()->create();
    $this->module = Module::factory()->for($this->teacher, 'teacher')->create();
});

/**
 * A minimal valid Conflict Map payload with the given event cards.
 *
 * @param  list<array<string, mixed>>  $events
 * @return array<string, mixed>
 */
function conflictMapPayload(array $events, ?int $quizId = null): array
{
    return [
        'quiz_id' => $quizId,
        'title' => 'Picture Conflict Map',
        'xp_reward' => 100,
        'passing_percentage' => 70,
        'zones' => [
            ['conflict_type' => 'character_vs_character', 'label' => 'Character vs Character'],
            ['conflict_type' => 'character_vs_nature', 'label' => 'Character vs Nature'],
        ],
        'events' => array_map(fn (array $event): array => [
            'target_conflict_type' => 'character_vs_nature',
            'points' => 10,
            ...$event,
        ], $events),
    ];
}

/**
 * A real 1x1 PNG upload (works without the GD extension that fake()->image() needs).
 */
function fakeCardPicture(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='),
    );
}

/**
 * Upload a card picture for the module through the studio endpoint and return its path.
 */
function uploadCardImage(User $teacher, Module $module, string $name = 'storm.png'): string
{
    return test()->actingAs($teacher)
        ->post(route('teachers.quizzes.the-conflict-map.images.store', $module->slug), [
            'image' => fakeCardPicture($name),
        ])
        ->assertCreated()
        ->json('path');
}

test('teacher uploads an event card picture into the module folder', function (): void {
    $response = $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.images.store', $this->module->slug), [
            'image' => fakeCardPicture('storm.png'),
        ]);

    $response->assertCreated()->assertJsonStructure(['path', 'url']);

    $path = $response->json('path');
    expect($path)->toStartWith("quizzes/conflict-map/{$this->module->id}/");
    Storage::disk('public')->assertExists($path);
});

test('teacher can upload multiple event card pictures in one request', function (): void {
    $response = $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.images.store', $this->module->slug), [
            'images' => [
                fakeCardPicture('fire.png'),
                fakeCardPicture('flood.png'),
                fakeCardPicture('argument.png'),
            ],
        ]);

    $response->assertCreated()->assertJsonStructure([
        'images' => [
            '*' => ['path', 'url', 'name'],
        ],
    ]);

    $images = $response->json('images');
    expect($images)->toHaveCount(3);

    foreach ($images as $img) {
        expect($img['path'])->toStartWith("quizzes/conflict-map/{$this->module->id}/");
        Storage::disk('public')->assertExists($img['path']);
    }

    expect($images[0]['name'])->toBe('fire')
        ->and($images[1]['name'])->toBe('flood')
        ->and($images[2]['name'])->toBe('argument');
});

test('multiple image uploads validate file types and batch size', function (): void {
    $this->actingAs($this->teacher)
        ->postJson(route('teachers.quizzes.the-conflict-map.images.store', $this->module->slug), [
            'images' => [
                fakeCardPicture('valid.png'),
                UploadedFile::fake()->create('invalid.txt', 10, 'text/plain'),
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('images.1');
});

test('event card uploads only accept pictures', function (): void {
    $this->actingAs($this->teacher)
        ->postJson(route('teachers.quizzes.the-conflict-map.images.store', $this->module->slug), [
            'image' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

test('teachers cannot upload pictures into another teacher module', function (): void {
    $otherTeacher = User::factory()->teacher()->create();

    $this->actingAs($otherTeacher)
        ->postJson(route('teachers.quizzes.the-conflict-map.images.store', $this->module->slug), [
            'image' => fakeCardPicture('storm.png'),
        ])
        ->assertForbidden();
});

test('an event card can be a picture without text', function (): void {
    $path = uploadCardImage($this->teacher, $this->module);

    $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.store', $this->module->slug), conflictMapPayload([
            ['image_path' => $path],
            ['event_text' => 'Two friends argue over the last raft.', 'target_conflict_type' => 'character_vs_character'],
        ]))
        ->assertSessionHasNoErrors();

    $pictureCard = ConflictMapEvent::query()->orderBy('sort_order')->first();

    expect($pictureCard->image_path)->toBe($path)
        ->and($pictureCard->image_disk)->toBe('public')
        ->and($pictureCard->event_text)->toBeNull()
        ->and($pictureCard->imageUrl())->toBe("/storage/{$path}");
});

test('a conflict map quiz can have exclusively image-only cards', function (): void {
    $path1 = uploadCardImage($this->teacher, $this->module, 'scene1.png');
    $path2 = uploadCardImage($this->teacher, $this->module, 'scene2.png');
    $path3 = uploadCardImage($this->teacher, $this->module, 'scene3.png');

    $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.store', $this->module->slug), conflictMapPayload([
            ['image_path' => $path1, 'target_conflict_type' => 'character_vs_character', 'card_letter' => 'Card A'],
            ['image_path' => $path2, 'target_conflict_type' => 'character_vs_nature', 'card_letter' => 'Card B'],
            ['image_path' => $path3, 'target_conflict_type' => 'character_vs_nature', 'card_letter' => 'Card C'],
        ]))
        ->assertSessionHasNoErrors();

    $events = ConflictMapEvent::query()->orderBy('sort_order')->get();
    expect($events)->toHaveCount(3);
    expect($events->every(fn (ConflictMapEvent $e): bool => $e->event_text === null))->toBeTrue();
    expect($events->every(fn (ConflictMapEvent $e): bool => ! empty($e->image_path)))->toBeTrue();
});

test('an event card needs text or a picture', function (): void {
    $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.store', $this->module->slug), conflictMapPayload([
            ['event_text' => ''],
        ]))
        ->assertSessionHasErrors('events.0.event_text');
});

test('event cards cannot reference pictures outside the module upload folder', function (string $path): void {
    $otherModule = Module::factory()->for($this->teacher, 'teacher')->create();
    Storage::disk('public')->put("quizzes/conflict-map/{$otherModule->id}/theirs.png", 'png');
    Storage::disk('public')->put('lectures/inline/image/secret.png', 'png');

    $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.store', $this->module->slug), conflictMapPayload([
            ['image_path' => str_replace('{other}', (string) $otherModule->id, $path)],
        ]))
        ->assertSessionHasErrors('events.0.image_path');
})->with([
    'another module folder' => ['quizzes/conflict-map/{other}/theirs.png'],
    'another feature folder' => ['lectures/inline/image/secret.png'],
    'path traversal' => ['quizzes/conflict-map/{other}/../../../lectures/inline/image/secret.png'],
]);

test('event cards cannot reference a picture that was never uploaded', function (): void {
    $this->actingAs($this->teacher)
        ->post(route('teachers.quizzes.the-conflict-map.store', $this->module->slug), conflictMapPayload([
            ['image_path' => "quizzes/conflict-map/{$this->module->id}/missing.png"],
        ]))
        ->assertSessionHasErrors('events.0.image_path');
});

test('replacing a card picture deletes the old file unless a copied quiz still uses it', function (): void {
    $sharedPath = uploadCardImage($this->teacher, $this->module, 'shared.png');
    $replacedPath = uploadCardImage($this->teacher, $this->module, 'replaced.png');
    $newPath = uploadCardImage($this->teacher, $this->module, 'new.png');

    $this->actingAs($this->teacher)->post(
        route('teachers.quizzes.the-conflict-map.store', $this->module->slug),
        conflictMapPayload([['image_path' => $sharedPath], ['image_path' => $replacedPath]]),
    );
    $original = Quiz::query()->latest('id')->firstOrFail();

    // A copy of the quiz keeps pointing at the shared picture.
    $this->actingAs($this->teacher)->post(
        route('teachers.quizzes.the-conflict-map.store', $this->module->slug),
        conflictMapPayload([['image_path' => $sharedPath]]),
    );

    // Editing the original swaps both pictures out.
    $this->actingAs($this->teacher)->post(
        route('teachers.quizzes.the-conflict-map.store', $this->module->slug),
        conflictMapPayload([['image_path' => $newPath]], $original->id),
    )->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($replacedPath);
    Storage::disk('public')->assertExists($sharedPath);
    Storage::disk('public')->assertExists($newPath);
});

test('deleting a conflict map quiz deletes its card pictures', function (): void {
    $path = uploadCardImage($this->teacher, $this->module);

    $this->actingAs($this->teacher)->post(
        route('teachers.quizzes.the-conflict-map.store', $this->module->slug),
        conflictMapPayload([['image_path' => $path]]),
    );
    $quiz = Quiz::query()->latest('id')->firstOrFail();

    $this->actingAs($this->teacher)
        ->delete(route('teachers.quizzes.destroy', [$this->module->slug, $quiz->id]))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
});
