<?php

use App\Enums\LectureContentType;
use App\Enums\ModuleStatus;
use App\Enums\ModuleTheme;
use App\Enums\PaperTheme;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a teacher can create a module with ordered lectures and multimedia content blocks', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Whispering Forest',
        'slug' => 'whispering-forest',
        'theme' => ModuleTheme::Forest,
        'xp_bonus' => 250,
        'status' => ModuleStatus::Published,
    ]);

    $lectureTwo = Lecture::factory()->for($module)->create([
        'title' => 'Chapter 2: Forest Boss Encounter',
        'slug' => 'chapter-2-forest-boss-encounter',
        'sort_order' => 2,
    ]);

    $lectureOne = Lecture::factory()->for($module)->create([
        'title' => 'Chapter 1: Phonics in the Woods',
        'slug' => 'chapter-1-phonics-in-the-woods',
        'description' => 'Learn foundational phonics with audio narration and story illustrations.',
        'body' => '<p>Full lecture body with rich formatting.</p>',
        'sort_order' => 1,
    ]);

    $videoBlock = LectureContent::factory()->for($lectureOne, 'lecture')->video()->create([
        'title' => 'Animated Phonics Guide',
        'sort_order' => 4,
    ]);

    $textBlock = LectureContent::factory()->for($lectureOne, 'lecture')->create([
        'type' => LectureContentType::Text,
        'title' => 'Introduction Passage',
        'content' => 'Once upon a time in the Whispering Forest...',
        'sort_order' => 1,
    ]);

    $imageBlock = LectureContent::factory()->for($lectureOne, 'lecture')->image()->create([
        'title' => 'Forest Map Illustration',
        'sort_order' => 2,
    ]);

    $audioBlock = LectureContent::factory()->for($lectureOne, 'lecture')->audio()->create([
        'title' => 'Read-Along Audio',
        'sort_order' => 3,
    ]);

    expect($teacher->modules)->toHaveCount(1)
        ->and($module->theme)->toBe(ModuleTheme::Forest)
        ->and($module->xp_bonus)->toBe(250)
        ->and($module->status)->toBe(ModuleStatus::Published)
        ->and($module->lectures->pluck('id')->all())->toBe([$lectureOne->id, $lectureTwo->id]);

    $orderedContents = $lectureOne->contents()->get();

    expect($orderedContents)->toHaveCount(4)
        ->and($orderedContents->pluck('id')->all())->toBe([
            $textBlock->id,
            $imageBlock->id,
            $audioBlock->id,
            $videoBlock->id,
        ])
        ->and($orderedContents[0]->type)->toBe(LectureContentType::Text)
        ->and($orderedContents[1]->type)->toBe(LectureContentType::Image)
        ->and($orderedContents[1]->mediaUrl())->toContain($imageBlock->file_path)
        ->and($orderedContents[2]->type)->toBe(LectureContentType::Audio)
        ->and($orderedContents[3]->type)->toBe(LectureContentType::Video);
});

test('deleting a module cascades to its lectures and lecture contents', function () {
    $module = Module::factory()->create();
    $lecture = Lecture::factory()->for($module)->create();
    $content = LectureContent::factory()->for($lecture, 'lecture')->audio()->create();

    $module->delete();

    $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    $this->assertDatabaseMissing('lectures', ['id' => $lecture->id]);
    $this->assertDatabaseMissing('lecture_contents', ['id' => $content->id]);
});

test('teacher dashboard only returns modules fetched from the database', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();

    $teacherModule = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Sunken Atlantis',
        'theme' => ModuleTheme::Ocean,
        'xp_bonus' => 300,
    ]);

    Module::factory()->for($otherTeacher, 'teacher')->create([
        'title' => 'Other Teacher Module',
    ]);

    $response = $this->actingAs($teacher)->get(route('teachers.dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('teachers/Dashboard')
        ->has('modules', 1)
        ->where('modules.0.id', $teacherModule->id)
        ->where('modules.0.title', 'Sunken Atlantis')
        ->where('modules.0.theme', 'ocean')
        ->where('modules.0.xp_bonus', 300)
    );
});

test('teacher dashboard displays newly created modules at the top', function () {
    $teacher = User::factory()->teacher()->create();

    $olderModule = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Ancient Library',
        'created_at' => now()->subDays(5),
    ]);

    $middleModule = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Crystal Cavern',
        'created_at' => now()->subDay(),
    ]);

    $newestModule = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Sky Sanctuary',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($teacher)->get(route('teachers.dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('teachers/Dashboard')
        ->has('modules', 3)
        ->where('modules.0.id', $newestModule->id)
        ->where('modules.0.title', 'Sky Sanctuary')
        ->where('modules.0.is_new', true)
        ->where('modules.1.id', $middleModule->id)
        ->where('modules.1.title', 'Crystal Cavern')
        ->where('modules.2.id', $olderModule->id)
        ->where('modules.2.title', 'Ancient Library')
    );
});

test('teacher can view the create module page and store a new module with a banner image and integer xp bonus', function () {
    Storage::fake('public');

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('teachers.modules.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/Module')
        );

    $bannerImage = UploadedFile::fake()->createWithContent(
        'cosmic-banner.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')
    );

    $response = $this->actingAs($teacher)->post(route('teachers.modules.store'), [
        'title' => 'Cosmic Nebula',
        'grade_level' => 'Grade 5 • Sci-Fi Writers Guild',
        'description' => 'Sci-fi storytelling and galaxy reading quests.',
        'theme' => 'cosmic',
        'icon' => '🚀',
        'cover_image' => $bannerImage,
        'xp_bonus' => 400,
        'boss_name' => 'Asteroid Dragon',
        'max_students' => 32,
        'status' => 'published',
    ]);

    $response->assertRedirect(route('teachers.dashboard'));

    $createdModule = Module::where('slug', 'cosmic-nebula')->firstOrFail();

    expect($createdModule->user_id)->toBe($teacher->id)
        ->and($createdModule->xp_bonus)->toBe(400)
        ->and($createdModule->cover_image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($createdModule->cover_image_path);
});

test('teacher can view module page with dynamic database lectures and contents', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Conflict and Community Values',
        'slug' => 'conflict-and-community-values',
    ]);

    $lecture = Lecture::factory()->for($module)->create([
        'title' => 'Chapter 1: Phonics and Folklore',
        'slug' => 'chapter-1-phonics-and-folklore',
        'xp_reward' => 200,
        'sort_order' => 1,
    ]);

    LectureContent::factory()->for($lecture, 'lecture')->create([
        'type' => LectureContentType::Text,
        'title' => 'Introduction Story Passage',
        'content' => 'Once upon a time in a peaceful barangay...',
    ]);

    $response = $this->actingAs($teacher)->get(route('teachers.modules.show', $module->slug));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('teachers/view/Module')
        ->has('module')
        ->has('lectures', 1)
        ->where('lectures.0.id', $lecture->id)
        ->where('lectures.0.title', 'Chapter 1: Phonics and Folklore')
        ->where('lectures.0.contents_count', 1)
    );
});

test('teacher viewing a module with zero lectures receives an empty lectures collection', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Empty Module Journey',
        'slug' => 'empty-module-journey',
    ]);

    $response = $this->actingAs($teacher)->get(route('teachers.modules.show', $module->slug));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('teachers/view/Module')
        ->where('lectures', [])
    );
});

test('teacher can view create lecture page and store a new lecture under a module', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Bayanihan Legends',
        'slug' => 'bayanihan-legends',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.lectures.create', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/Lecture')
            ->where('module.title', 'Bayanihan Legends')
        );

    $response = $this->actingAs($teacher)->post(route('teachers.lectures.store', $module->slug), [
        'title' => 'Motivation and Background Information',
        'description' => 'Uncover historical context and character motivations.',
        'body' => '<p>Lecture body text...</p>',
        'xp_reward' => 250,
        'is_published' => true,
    ]);

    $response->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->assertDatabaseHas('lectures', [
        'module_id' => $module->id,
        'title' => 'Motivation and Background Information',
        'slug' => 'motivation-and-background-information',
        'xp_reward' => 250,
        'sort_order' => 1,
    ]);
});

test('teacher can view create lecture content page and store a content block', function () {
    Storage::fake('public');

    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create([
        'title' => 'Understanding Conflict',
        'slug' => 'understanding-conflict',
    ]);

    $this->actingAs($teacher)
        ->get(route('teachers.lectures.contents.create', ['module' => $module->slug, 'lecture' => $lecture->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/LectureContent')
            ->where('lecture.title', 'Understanding Conflict')
        );

    $audioFile = UploadedFile::fake()->create('narration.mp3', 500, 'audio/mpeg');

    $response = $this->actingAs($teacher)->post(route('teachers.lectures.contents.store', [
        'module' => $module->slug,
        'lecture' => $lecture->slug,
    ]), [
        'type' => 'audio',
        'title' => 'Teacher Narration Audio',
        'media_file' => $audioFile,
        'duration_seconds' => 180,
        'caption' => 'Guided voice narration for the story passage',
    ]);

    $response->assertRedirect(route('teachers.lectures.contents.create', [
        'module' => $module->slug,
        'lecture' => $lecture->slug,
    ]));

    $content = LectureContent::where('lecture_id', $lecture->id)->firstOrFail();

    expect($content->type)->toBe(LectureContentType::Audio)
        ->and($content->title)->toBe('Teacher Narration Audio')
        ->and($content->duration_seconds)->toBe(180)
        ->and($content->file_path)->not->toBeNull();

    Storage::disk('public')->assertExists($content->file_path);
});

test('background save queue receives json with the content id when storing and updating a lecture page', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create();
    $routeParams = ['module' => $module->slug, 'lecture' => $lecture->slug];

    $storeResponse = $this->actingAs($teacher)->postJson(route('teachers.lectures.contents.store', $routeParams), [
        'type' => 'text',
        'layout_style' => 'interactive_story',
        'title' => 'Queued Page',
        'content' => '<p>First draft</p>',
    ]);

    $content = LectureContent::where('lecture_id', $lecture->id)->firstOrFail();
    $storeResponse->assertCreated()->assertExactJson(['id' => $content->id]);

    $this->actingAs($teacher)->postJson(route('teachers.lectures.contents.update', [...$routeParams, 'content' => $content->id]), [
        'type' => 'text',
        'layout_style' => 'interactive_story',
        'title' => 'Queued Page Updated',
        'content' => '<p>Second draft</p>',
    ])->assertOk()->assertExactJson(['id' => $content->id]);

    expect($content->fresh()->title)->toBe('Queued Page Updated');
});

test('lecture pages remember their paper theme and students read them on the same paper', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create(['status' => ModuleStatus::Published]);
    $lecture = Lecture::factory()->for($module)->create(['is_published' => true]);
    $routeParams = ['module' => $module->slug, 'lecture' => $lecture->slug];

    $this->actingAs($teacher)->postJson(route('teachers.lectures.contents.store', $routeParams), [
        'type' => 'text',
        'title' => 'Parchment Page',
        'content' => '<p>Once upon a time</p>',
        'paper_theme' => 'parchment',
    ])->assertCreated();

    $content = LectureContent::where('lecture_id', $lecture->id)->firstOrFail();
    expect($content->paper_theme)->toBe(PaperTheme::Parchment);

    $this->actingAs($teacher)->postJson(route('teachers.lectures.contents.update', [...$routeParams, 'content' => $content->id]), [
        'type' => 'text',
        'title' => 'Notebook Page',
        'content' => '<p>Once upon a time</p>',
        'paper_theme' => 'lined',
    ])->assertOk();

    expect($content->fresh()->paper_theme)->toBe(PaperTheme::Lined);

    $this->actingAs($teacher)
        ->get(route('teachers.lectures.contents.create', $routeParams))
        ->assertInertia(fn (Assert $page) => $page->where('existing_contents.0.paper_theme', 'lined'));

    $this->actingAs($student)
        ->get(route('students.lectures.show', $routeParams))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/modules/LectureContent')
            ->where('contents.0.paper_theme', 'lined')
        );
});

test('lecture pages default to classic paper and reject unknown paper themes', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create();
    $routeParams = ['module' => $module->slug, 'lecture' => $lecture->slug];

    $this->actingAs($teacher)->postJson(route('teachers.lectures.contents.store', $routeParams), [
        'type' => 'text',
        'content' => '<p>Plain page</p>',
    ])->assertCreated();

    expect(LectureContent::where('lecture_id', $lecture->id)->firstOrFail()->paper_theme)->toBe(PaperTheme::Classic);

    $this->actingAs($teacher)->postJson(route('teachers.lectures.contents.store', $routeParams), [
        'type' => 'text',
        'content' => '<p>Odd page</p>',
        'paper_theme' => 'glitter',
    ])->assertUnprocessable()->assertJsonValidationErrors('paper_theme');
});

test('teacher can upload an inline lecture video and receives its public url', function () {
    Storage::fake('public');

    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create();

    $video = UploadedFile::fake()->create('creation-story.mp4', 120_000, 'video/mp4');

    $response = $this->actingAs($teacher)->post(
        route('teachers.lectures.media.store', ['module' => $module->slug, 'lecture' => $lecture->slug]),
        ['file' => $video],
        ['Accept' => 'application/json'],
    );

    $response->assertCreated()
        ->assertJsonPath('name', 'creation-story.mp4')
        ->assertJsonPath('kind', 'video');

    $storedPath = 'lectures/inline/video/'.$video->hashName();
    Storage::disk('public')->assertExists($storedPath);
    expect($response->json('url'))->toBe(Storage::disk('public')->url($storedPath));
});

test('inline lecture media upload rejects non media files and other teachers', function () {
    Storage::fake('public');

    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create();
    $uploadUrl = route('teachers.lectures.media.store', ['module' => $module->slug, 'lecture' => $lecture->slug]);

    $this->actingAs($teacher)
        ->post($uploadUrl, ['file' => UploadedFile::fake()->create('notes.zip', 10, 'application/zip')], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    $this->actingAs(User::factory()->teacher()->create())
        ->post($uploadUrl, ['file' => UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4')], ['Accept' => 'application/json'])
        ->assertForbidden();

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('background save queue receives json validation errors and forbidden responses', function () {
    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create();
    $lecture = Lecture::factory()->for($module)->create();
    $routeParams = ['module' => $module->slug, 'lecture' => $lecture->slug];

    $this->actingAs($teacher)
        ->postJson(route('teachers.lectures.contents.store', $routeParams), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');

    $otherTeacher = User::factory()->teacher()->create();

    $this->actingAs($otherTeacher)
        ->postJson(route('teachers.lectures.contents.store', $routeParams), [
            'type' => 'text',
            'title' => 'Intruder Page',
            'content' => '<p>Nope</p>',
        ])
        ->assertForbidden();

    expect(LectureContent::where('lecture_id', $lecture->id)->count())->toBe(0);
});

test('teacher can store an interactive story lecture page with word meanings and full-page read-aloud narration audio', function () {
    Storage::fake('public');

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Published,
    ]);
    $lecture = Lecture::factory()->for($module)->create([
        'title' => 'The Lantern of Whispering Pines',
        'slug' => 'the-lantern-of-whispering-pines',
        'is_published' => true,
    ]);

    $narrationAudio = UploadedFile::fake()->create('page-1-reading.mp3', 640, 'audio/mpeg');

    $response = $this->actingAs($teacher)->post(route('teachers.lectures.contents.store', [
        'module' => $module->slug,
        'lecture' => $lecture->slug,
    ]), [
        'type' => 'text',
        'layout_style' => 'interactive_story',
        'title' => 'Page 1: The Glowing Bark',
        'content' => '<p>Mira held up her <span class="vocab-gloss-token" data-vocab-word="luminous" data-vocab-pronunciation="LOO-muh-nus" data-vocab-meaning="Shining brightly in the dark.">luminous</span> brass lantern.</p>',
        'narration_audio_file' => $narrationAudio,
        'vocabulary_glosses' => [
            [
                'word' => 'luminous',
                'pronunciation' => 'LOO-muh-nus',
                'meaning' => 'Shining brightly in the dark.',
            ],
        ],
    ]);

    $response->assertRedirect(route('teachers.lectures.contents.create', [
        'module' => $module->slug,
        'lecture' => $lecture->slug,
    ]));

    $content = LectureContent::where('lecture_id', $lecture->id)->firstOrFail();

    expect($content->layout_style)->toBe('interactive_story')
        ->and($content->narration_audio_path)->not->toBeNull()
        ->and($content->narration_audio_name)->toBe('page-1-reading.mp3')
        ->and($content->narrationAudioUrl())->toContain($content->narration_audio_path)
        ->and($content->vocabulary_glosses)->toHaveCount(1)
        ->and($content->vocabulary_glosses[0]['word'])->toBe('luminous')
        ->and($content->vocabulary_glosses[0]['meaning'])->toBe('Shining brightly in the dark.');

    Storage::disk('public')->assertExists($content->narration_audio_path);

    $this->actingAs($student)
        ->get(route('students.modules.show', $module->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('lectures.0.contents.0.layout_style', 'interactive_story')
            ->where('lectures.0.contents.0.narration_audio_name', 'page-1-reading.mp3')
            ->where('lectures.0.contents.0.vocabulary_glosses.0.word', 'luminous')
        );

    $this->actingAs($student)
        ->get(route('students.lectures.show', ['module' => $module->slug, 'lecture' => $lecture->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/modules/LectureContent')
            ->where('lecture.title', 'The Lantern of Whispering Pines')
            ->where('contents.0.layout_style', 'interactive_story')
            ->where('contents.0.narration_audio_name', 'page-1-reading.mp3')
            ->where('contents.0.vocabulary_glosses.0.word', 'luminous')
        );
});

test('teacher can open the unified quiz studio with existing quizzes and delete a quiz', function () {
    $teacher = User::factory()->teacher()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Conflict Resolution Studio Module',
        'slug' => 'conflict-resolution-studio-module',
    ]);

    $this->actingAs($teacher)->post(route('teachers.quizzes.multiple-choice.store', $module->slug), [
        'title' => 'Barangay Harmony Check',
        'description' => 'Assess character choices in community disputes.',
        'xp_reward' => 150,
        'passing_percentage' => 70,
        'is_published' => true,
        'contexts' => [
            [
                'scenario' => 'Two neighbors disagree over a mango tree branch.',
                'hint' => 'Focus on peaceful dialogue.',
            ],
        ],
        'questions' => [
            [
                'question_text' => 'What is the most peaceful first step?',
                'selection_type' => 'single',
                'explanation' => 'Calm dialogue resolves misunderstandings.',
                'points' => 10,
                'options' => [
                    ['option_text' => 'Talk calmly with the neighbor', 'is_correct' => true],
                    ['option_text' => 'Cut the tree down immediately', 'is_correct' => false],
                ],
            ],
        ],
    ])->assertRedirect(route('teachers.modules.show', $module->slug));

    $quiz = Quiz::where('module_id', $module->id)->firstOrFail();

    $student = User::factory()->student()->create();
    $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->where('module.slug', 'conflict-resolution-studio-module')
            ->where('quiz.title', 'Barangay Harmony Check')
            ->where('quiz.type', 'multiple_choice')
            ->has('quiz.questions', 1)
        );

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.studio', ['module' => $module->slug, 'type' => 'color-the-conflict']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/create/quizes/QuizStudio')
            ->where('module.slug', 'conflict-resolution-studio-module')
            ->where('initialQuizType', 'color-the-conflict')
            ->has('existingQuizzes', 1)
            ->where('existingQuizzes.0.title', 'Barangay Harmony Check')
            ->where('existingQuizzes.0.type', 'multiple_choice')
            ->has('existingQuizzes.0.questions', 1)
        );

    $this->actingAs($teacher)
        ->delete(route('teachers.quizzes.destroy', ['module' => $module->slug, 'quiz' => $quiz->id]))
        ->assertRedirect();

    $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
});
