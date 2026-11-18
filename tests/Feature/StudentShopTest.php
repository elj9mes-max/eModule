<?php

use App\Enums\QuizType;
use App\Enums\ShopItemCategory;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\ShopItem;
use App\Models\ShopPurchase;
use App\Models\StudentShopItem;
use App\Models\User;
use App\Services\Gamification\LevelProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->student = User::factory()->student()->create();
    $this->profile = $this->student->getOrCreateStudentProfile();
    $this->profile->update(['total_gems' => 150, 'level' => 3]);
});

test('student buys an item with earned gems', function (): void {
    $item = ShopItem::factory()->create(['price' => 120]);

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $item))
        ->assertOk()
        ->assertJsonPath('purchase.gem_balance', 30)
        ->assertJsonPath('purchase.quantity', 1);

    expect($this->profile->fresh())
        ->total_gems->toBe(150)
        ->gems_spent->toBe(120);

    $this->assertDatabaseHas('shop_purchases', [
        'user_id' => $this->student->id,
        'shop_item_id' => $item->id,
        'price_paid' => 120,
    ]);
});

test('student cannot buy an item they cannot afford', function (): void {
    $item = ShopItem::factory()->create(['price' => 151]);

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $item))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'You need 1 more gems']);

    expect($this->profile->fresh()->gems_spent)->toBe(0);
    expect(ShopPurchase::count())->toBe(0);
});

test('spent gems cannot be spent again', function (): void {
    $this->profile->update(['gems_spent' => 100]);
    $item = ShopItem::factory()->create(['price' => 60]);

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $item))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'You need 10 more gems']);
});

test('student cannot buy the same cosmetic twice', function (): void {
    $item = ShopItem::factory()->create(['price' => 50]);

    $this->actingAs($this->student)->postJson(route('students.shop.purchase', $item))->assertOk();

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $item))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'You already own']);

    expect($this->profile->fresh()->gems_spent)->toBe(50);
});

test('boosters stack up to their holding limit', function (): void {
    $booster = ShopItem::factory()->booster(maxQuantity: 2)->create(['price' => 25]);

    $this->actingAs($this->student)->postJson(route('students.shop.purchase', $booster))->assertOk();
    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $booster))
        ->assertOk()
        ->assertJsonPath('purchase.quantity', 2);

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $booster))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'You can only hold 2']);

    expect($this->profile->fresh()->gems_spent)->toBe(50);
});

test('items above the student level stay locked', function (): void {
    $item = ShopItem::factory()->create(['price' => 10, 'min_level' => 4]);

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $item))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'Reach level 4']);
});

test('items that are off sale cannot be bought', function (): void {
    $item = ShopItem::factory()->inactive()->create(['price' => 10]);

    $this->actingAs($this->student)
        ->postJson(route('students.shop.purchase', $item))
        ->assertForbidden();
});

test('only students can buy shop items', function (): void {
    $item = ShopItem::factory()->create(['price' => 10]);

    $this->actingAs(User::factory()->teacher()->create())
        ->postJson(route('students.shop.purchase', $item))
        ->assertForbidden();
});

test('guests are sent to login instead of buying', function (): void {
    $item = ShopItem::factory()->create(['price' => 10]);

    $this->post(route('students.shop.purchase', $item))->assertRedirect(route('login'));
});

test('student equips an owned cosmetic', function (): void {
    $item = ShopItem::factory()->create(['price' => 10]);
    StudentShopItem::create(['user_id' => $this->student->id, 'shop_item_id' => $item->id, 'quantity' => 1]);

    $this->actingAs($this->student)
        ->patchJson(route('students.shop.equip', $item))
        ->assertOk();

    expect($this->profile->fresh()->equipped_avatar_item_id)->toBe($item->id);
});

test('student cannot equip an item they do not own', function (): void {
    $item = ShopItem::factory()->create(['price' => 10]);

    $this->actingAs($this->student)
        ->patchJson(route('students.shop.equip', $item))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'before equipping']);

    expect($this->profile->fresh()->equipped_avatar_item_id)->toBeNull();
});

test('boosters cannot be equipped', function (): void {
    $booster = ShopItem::factory()->booster()->create();
    StudentShopItem::create(['user_id' => $this->student->id, 'shop_item_id' => $booster->id, 'quantity' => 1]);

    $this->actingAs($this->student)
        ->patchJson(route('students.shop.equip', $booster))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shop' => 'used inside quizzes']);
});

test('profile recalculation after a regrade keeps spent gems spent', function (): void {
    $module = Module::factory()->for(User::factory()->teacher(), 'teacher')->create();
    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Gem Quiz',
        'slug' => 'gem-quiz',
        'type' => QuizType::MultipleChoice,
        'passing_percentage' => 70,
        'sort_order' => 1,
        'is_published' => true,
    ]);
    QuizAttempt::create([
        'user_id' => $this->student->id,
        'quiz_id' => $quiz->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'stars' => 3,
        'gems_earned' => 150,
        'xp_earned' => 0,
        'is_passed' => true,
    ]);
    $item = ShopItem::factory()->create(['price' => 120]);
    $this->actingAs($this->student)->postJson(route('students.shop.purchase', $item))->assertOk();

    $profile = app(LevelProgressionService::class)->recalculateStudentProfile($this->student->id);

    expect($profile->total_gems)->toBe(150)
        ->and($profile->gems_spent)->toBe(120)
        ->and($profile->gemBalance())->toBe(30);
});

test('shop page lists items on sale with the student inventory and balance', function (): void {
    $owned = ShopItem::factory()->create(['key' => 'owned-avatar', 'price' => 40]);
    ShopItem::factory()->inactive()->create(['key' => 'retired-avatar']);
    StudentShopItem::create(['user_id' => $this->student->id, 'shop_item_id' => $owned->id, 'quantity' => 1]);
    $this->profile->update(['gems_spent' => 40, 'equipped_avatar_item_id' => $owned->id]);

    $this->actingAs($this->student)
        ->get(route('students.shop'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('students/Shop')
            ->where('user.gamification.gem_balance', 110)
            ->has('shop.items', 1)
            ->where('shop.items.0.key', 'owned-avatar')
            ->where('shop.inventory', ['owned-avatar' => 1])
            ->where('shop.equipped', ['avatar' => 'owned-avatar']));
});

test('equipped cosmetic is shared with every student page', function (ShopItemCategory $category, string $metadataKey, string $sharedProp): void {
    $cosmetic = ShopItem::factory()->create([
        'category' => $category,
        'metadata' => [$metadataKey => 'theme-under-test'],
    ]);
    StudentShopItem::create(['user_id' => $this->student->id, 'shop_item_id' => $cosmetic->id, 'quantity' => 1]);

    $this->actingAs($this->student)
        ->get(route('students.settings'))
        ->assertInertia(fn (Assert $page) => $page->where("auth.user.{$sharedProp}", null));

    $this->actingAs($this->student)->patchJson(route('students.shop.equip', $cosmetic))->assertOk();

    $this->actingAs($this->student->fresh())
        ->get(route('students.settings'))
        ->assertInertia(fn (Assert $page) => $page->where("auth.user.{$sharedProp}", 'theme-under-test'));
})->with([
    'header background' => [ShopItemCategory::Console, 'header_skin', 'header_skin'],
    'trail theme' => [ShopItemCategory::Background, 'trail_theme', 'trail_theme'],
]);
