<?php

use App\Enums\ModuleStatus;
use App\Enums\QuestionSelectionType;
use App\Enums\QuizType;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use App\Models\User;
use App\Services\Cache\EModuleCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('student module map and quiz arena eliminate lazy loading and reduce queries on cache hit', function () {
    Model::preventLazyLoading(true);

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Published,
    ]);

    $lecture = Lecture::factory()->for($module)->create(['sort_order' => 1]);
    $lectureTwo = Lecture::factory()->for($module)->create(['sort_order' => 2]);
    LectureContent::factory()->for($lecture, 'lecture')->create(['sort_order' => 1]);
    LectureContent::factory()->for($lecture, 'lecture')->create(['sort_order' => 2]);

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Comprehension Check',
        'slug' => 'comprehension-check',
        'type' => QuizType::MultipleChoice,
        'sort_order' => 3,
        'is_published' => true,
    ]);

    $secondQuiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Conflict Color Quest',
        'slug' => 'conflict-color-quest',
        'type' => QuizType::ColorTheConflict,
        'sort_order' => 4,
        'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'What is the main theme?',
        'selection_type' => QuestionSelectionType::Single,
        'points' => 10,
        'sort_order' => 1,
    ]);
    QuizQuestionOption::create([
        'question_id' => $question->id,
        'option_text' => 'Community cooperation',
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    // Cold visit populates caches
    $this->actingAs($student)
        ->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Progress')
            ->where('quizzes.0.questions_count', 1)
            ->missing('quizzes.0.questions')
        );

    // Warm visit should not query lectures, lecture_contents, or quiz child tables
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($student)
        ->get(route('students.modules.show', $module->slug))
        ->assertOk();

    $warmQueries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($warmQueries->filter(fn (string $sql): bool => str_contains($sql, 'from "lectures"') || str_contains($sql, 'from "lecture_contents"') || str_contains($sql, 'from "quiz_questions"')))
        ->toHaveCount(0);

    // Quiz arena visit with >1 sibling quizzes (triggers Eloquent hydrate preventsLazyLoading) must succeed for all formats
    $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->has('quiz.questions', 1)
            ->where('quiz.cards', [])
        );

    $this->actingAs($student)
        ->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $secondQuiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/views/quiz/Quiz')
            ->where('quiz.questions', [])
        );

    // Lecture reader & teacher routes also execute cleanly with preventLazyLoading active
    $this->actingAs($student)
        ->get(route('students.lectures.show', ['module' => $module->slug, 'lecture' => $lectureTwo->slug]))
        ->assertOk();

    $this->actingAs($teacher)
        ->get(route('teachers.modules.show', $module->slug))
        ->assertOk();

    $this->actingAs($teacher)
        ->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk();
});

test('module, lecture, content, and quiz mutations invalidate student and teacher caches immediately', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'First Realm',
        'slug' => 'first-realm',
        'status' => ModuleStatus::Published,
    ]);
    $module->enrollments()->create(['user_id' => $student->id]);

    // Warm student and teacher dashboards
    $this->actingAs($student)->get(route('students.classes.code-adventures'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('modules', 1));

    $this->actingAs($teacher)->get(route('teachers.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('modules', 1));

    // Teacher creates a second module
    $this->actingAs($teacher)->post(route('teachers.modules.store'), [
        'title' => 'Second Realm',
        'theme' => 'ocean',
        'icon' => '🌊',
        'xp_bonus' => 300,
        'status' => 'published',
    ])->assertRedirect(route('teachers.dashboard'));

    $secondModule = Module::where('title', 'Second Realm')->firstOrFail();
    $secondModule->enrollments()->create(['user_id' => $student->id]);

    // Both dashboards immediately reflect the newly created module
    $this->actingAs($student)->get(route('students.classes.code-adventures'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('modules', 2));

    $this->actingAs($teacher)->get(route('teachers.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('modules', 2));

    // Warm student progress map for First Realm
    $this->actingAs($student)->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('lectures', 0)->has('quizzes', 0));

    // Teacher adds a lecture and content block
    $this->actingAs($teacher)->post(route('teachers.lectures.store', $module->slug), [
        'title' => 'Chapter One',
        'xp_reward' => 150,
        'is_published' => true,
    ])->assertRedirect(route('teachers.modules.show', $module->slug));

    $lecture = Lecture::where('module_id', $module->id)->firstOrFail();

    $this->actingAs($teacher)->post(route('teachers.lectures.contents.store', [
        'module' => $module->slug,
        'lecture' => $lecture->slug,
    ]), [
        'type' => 'text',
        'title' => 'Opening Scene',
        'content' => 'The wind whispered across the canyon.',
    ])->assertRedirect();

    $this->actingAs($student)->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('lectures', 1)
            ->where('lectures.0.contents_count', 1)
        );

    $this->actingAs($student)->get(route('students.lectures.show', ['module' => $module->slug, 'lecture' => $lecture->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contents', 1)
            ->where('contents.0.title', 'Opening Scene')
        );

    // Teacher adds and deletes a quiz -> progress map updates each time
    $this->actingAs($teacher)->post(route('teachers.quizzes.reflection.store', $module->slug), [
        'title' => 'Canyon Reflection',
        'xp_reward' => 150,
        'passing_percentage' => 70,
        'is_published' => true,
        'prompts' => [
            [
                'prompt_text' => 'What stood out in the opening scene?',
                'min_words' => 3,
                'max_words' => 100,
                'points' => 10,
            ],
        ],
    ])->assertRedirect(route('teachers.modules.show', $module->slug));

    $this->actingAs($student)->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('quizzes', 1));

    $quiz = Quiz::where('module_id', $module->id)->firstOrFail();

    $this->actingAs($teacher)->delete(route('teachers.quizzes.destroy', [
        'module' => $module->slug,
        'quiz' => $quiz->id,
    ]))->assertRedirect();

    $this->actingAs($student)->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('quizzes', 0));
});

test('student completion, quiz submission, and teacher grading invalidate progress, stats, and analytics', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $module = Module::factory()->for($teacher, 'teacher')->create([
        'status' => ModuleStatus::Published,
    ]);

    $lecture = Lecture::factory()->for($module)->create(['sort_order' => 1]);
    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Thoughtful Essay',
        'slug' => 'thoughtful-essay',
        'type' => QuizType::Reflection,
        'passing_percentage' => 70,
        'xp_reward' => 200,
        'sort_order' => 2,
        'is_published' => true,
    ]);
    $prompt = ReflectionPrompt::create([
        'quiz_id' => $quiz->id,
        'prompt_text' => 'Share your key insight.',
        'min_words' => 3,
        'max_words' => 100,
        'points' => 10,
        'sort_order' => 1,
    ]);

    // Warm all caches before mutations
    $this->actingAs($student)->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('completedLectureIds', [])
            ->where('completedQuizIds', [])
        );

    $this->actingAs($teacher)->get(route('teachers.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('pending_grading_count', 0));

    $this->actingAs($teacher)->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('analytics.total_attempts', 0));

    // Student completes lecture
    $this->actingAs($student)
        ->postJson(route('students.lectures.complete', ['module' => $module->slug, 'lecture' => $lecture->slug]))
        ->assertOk();

    // Student submits reflection quiz
    $this->actingAs($student)
        ->postJson(route('students.quizzes.submit', ['module' => $module->slug, 'quiz' => $quiz->slug]), [
            'time_spent_seconds' => 30,
            'answers' => [
                (string) $prompt->id => 'Collaboration helps everyone succeed together.',
            ],
        ])
        ->assertOk();

    // Progress map immediately reflects both completion and pending review quiz
    $this->actingAs($student)->get(route('students.modules.show', $module->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('completedLectureIds', [$lecture->id])
            ->where('completedQuizIds', [$quiz->id])
            ->where('pendingQuizIds', [$quiz->id])
        );

    // Teacher dashboard & quiz analytics immediately reflect pending review
    $this->actingAs($teacher)->get(route('teachers.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('pending_grading_count', 1));

    $this->actingAs($teacher)->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('analytics.total_attempts', 1)
            ->where('analytics.pending_review_count', 1)
        );

    // Teacher grades the attempt
    $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
    $answer = $attempt->answers()->firstOrFail();

    $this->actingAs($teacher)->postJson(route('teachers.grading.grade', ['attempt' => $attempt->id]), [
        'answers' => [
            ['id' => $answer->id, 'points_earned' => 9, 'teacher_notes' => 'Insightful answer.'],
        ],
        'teacher_feedback' => 'Well articulated!',
    ])->assertOk();

    // Teacher dashboard drops back to 0 pending; analytics and student quiz page update immediately
    $this->actingAs($teacher)->get(route('teachers.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('pending_grading_count', 0));

    $this->actingAs($teacher)->get(route('teachers.quizzes.results', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('analytics.graded_count', 1)
            ->where('analytics.pending_review_count', 0)
            ->where('analytics.average_score', 9)
        );

    $this->actingAs($student)->get(route('students.quizzes.show', ['module' => $module->slug, 'quiz' => $quiz->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('existingAttempt.status', 'completed')
            ->where('existingAttempt.score', 9)
            ->where('existingAttempt.teacher_feedback', 'Well articulated!')
        );
});

test('cached modules recompute is_new dynamically as time advances and ranking invalidates on student registration', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create(['name' => 'Alice Explorer']);

    $freshModule = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'Fresh Realm',
        'status' => ModuleStatus::Published,
        'created_at' => now(),
    ]);
    $freshModule->enrollments()->create(['user_id' => $student->id]);

    $this->actingAs($student)->get(route('students.classes.code-adventures'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('modules.0.is_new', true));

    // Travel past the 2-day is_new window without invalidating the module cache
    $this->travel(3)->days();

    $this->actingAs($student)->get(route('students.classes.code-adventures'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('modules.0.is_new', false));

    // Warm ranking cache, then register a new student and verify ranking updates immediately
    $this->actingAs($student)->get(route('students.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('classmates', 1));

    User::factory()->student()->create(['name' => 'Zed Voyager']);

    $this->actingAs($student)->get(route('students.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('classmates', 2)
            ->where('classmates.1.name', 'Zed Voyager')
        );
});

test('teacher grading dashboard paginates submissions cleanly and supports search filtering', function () {
    $teacher = User::factory()->teacher()->create();
    $targetStudent = User::factory()->student()->create(['name' => 'Zara Highlight']);
    $otherStudent = User::factory()->student()->create(['name' => 'Ben Regular']);
    $module = Module::factory()->for($teacher, 'teacher')->create();

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Grading Volume Quiz',
        'slug' => 'grading-volume-quiz',
        'type' => QuizType::Reflection,
        'sort_order' => 1,
        'is_published' => true,
    ]);

    for ($i = 1; $i <= 28; $i++) {
        QuizAttempt::create([
            'user_id' => $i === 28 ? $targetStudent->id : $otherStudent->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => $i,
            'status' => 'pending_review',
            'max_score' => 10,
        ]);
    }

    // Page 1 returns 25 items and accurate totals
    $this->actingAs($teacher)->get(route('teachers.grading.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teachers/Grading')
            ->has('submissions', 25)
            ->where('stats.pending_count', 28)
            ->where('pagination.current_page', 1)
            ->where('pagination.last_page', 2)
            ->where('pagination.total', 28)
        );

    // Page 2 returns remaining 3 items
    $this->actingAs($teacher)->get(route('teachers.grading.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('submissions', 3)
            ->where('pagination.current_page', 2)
        );

    // Search narrows down to target student
    $this->actingAs($teacher)->get(route('teachers.grading.index', ['search' => 'Zara']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('submissions', 1)
            ->where('submissions.0.student.name', 'Zara Highlight')
        );
});

test('cache service works reliably with database cache store and serializable_classes disabled', function () {
    config(['cache.default' => 'database']);
    Cache::forgetDriver('database');

    $teacher = User::factory()->teacher()->create();
    $module = Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'DB Store Realm',
        'status' => ModuleStatus::Published,
    ]);

    $service = app(EModuleCacheService::class);
    $first = $service->getStudentModules();

    expect($first)->toHaveCount(1)
        ->and($first[0]['title'])->toBe('DB Store Realm');

    // Warm read from database cache table
    $second = $service->getStudentModules();
    expect($second)->toHaveCount(1);

    // Create another module and verify DatabaseStore::increment bumps epoch properly
    Module::factory()->for($teacher, 'teacher')->create([
        'title' => 'DB Store Realm Two',
        'status' => ModuleStatus::Published,
    ]);

    $third = $service->getStudentModules();
    expect($third)->toHaveCount(2);
});
