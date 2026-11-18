<?php

namespace App\Services\Gamification;

use App\Enums\ShopItemCategory;
use App\Models\ShopItem;
use App\Models\ShopPurchase;
use App\Models\StudentProfile;
use App\Models\StudentShopItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Server-authoritative Gem Bazaar: every gem spend and equip goes through here.
 */
class ShopService
{
    /**
     * Spend the student's gems on one unit of a shop item.
     *
     * @return array{gem_balance: int, gems_spent: int, quantity: int}
     *
     * @throws ValidationException
     */
    public function purchase(User $student, ShopItem $item): array
    {
        return DB::transaction(function () use ($student, $item): array {
            $profile = $this->lockProfile($student);

            if ($profile->level < $item->min_level) {
                throw $this->failure("Reach level {$item->min_level} to unlock {$item->name}.");
            }

            $inventory = StudentShopItem::query()
                ->where('user_id', $student->id)
                ->where('shop_item_id', $item->id)
                ->first();

            $ownedQuantity = (int) ($inventory?->quantity ?? 0);

            if ($ownedQuantity >= $item->max_quantity) {
                throw $this->failure($item->max_quantity === 1
                    ? "You already own {$item->name}."
                    : "You can only hold {$item->max_quantity} of {$item->name} at a time.");
            }

            $balance = $profile->gemBalance();

            if ($balance < $item->price) {
                $missing = $item->price - $balance;

                throw $this->failure("You need {$missing} more gems for {$item->name}.");
            }

            ShopPurchase::create([
                'user_id' => $student->id,
                'shop_item_id' => $item->id,
                'price_paid' => $item->price,
            ]);

            $inventory ??= new StudentShopItem(['user_id' => $student->id, 'shop_item_id' => $item->id]);
            $inventory->quantity = $ownedQuantity + 1;
            $inventory->save();

            $profile->gems_spent += $item->price;
            $profile->save();

            return [
                'gem_balance' => $profile->gemBalance(),
                'gems_spent' => (int) $profile->gems_spent,
                'quantity' => $inventory->quantity,
            ];
        });
    }

    /**
     * Wear or apply an owned cosmetic item.
     *
     * @throws ValidationException
     */
    public function equip(User $student, ShopItem $item): void
    {
        $column = $item->category->equippedColumn();

        if ($column === null) {
            throw $this->failure("{$item->name} is used inside quizzes and can't be equipped.");
        }

        $isOwned = StudentShopItem::query()
            ->where('user_id', $student->id)
            ->where('shop_item_id', $item->id)
            ->where('quantity', '>', 0)
            ->exists();

        if (! $isOwned) {
            throw $this->failure("Buy {$item->name} before equipping it.");
        }

        $profile = $student->getOrCreateStudentProfile();
        $profile->{$column} = $item->id;
        $profile->save();
    }

    /**
     * Total gems a student has ever spent, rebuilt from the purchase ledger.
     */
    public function gemsSpentBy(int $userId): int
    {
        return (int) ShopPurchase::query()->where('user_id', $userId)->sum('price_paid');
    }

    /**
     * The catalog and the student's inventory, shaped for the shop page.
     *
     * @return array{
     *     items: list<array{key: string, name: string, category: string, rarity: string, description: string|null, perk: string|null, price: int, min_level: int, max_quantity: int, metadata: array<string, mixed>|null}>,
     *     inventory: array<string, int>,
     *     equipped: array<string, string|null>
     * }
     */
    public function storefrontFor(User $student): array
    {
        $profile = $student->getOrCreateStudentProfile();

        $items = ShopItem::query()
            ->active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $inventory = StudentShopItem::query()
            ->where('student_shop_items.user_id', $student->id)
            ->where('quantity', '>', 0)
            ->join('shop_items', 'shop_items.id', '=', 'student_shop_items.shop_item_id')
            ->pluck('student_shop_items.quantity', 'shop_items.key')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();

        $equippedIds = array_filter([
            ShopItemCategory::Avatar->value => $profile->equipped_avatar_item_id,
            ShopItemCategory::Background->value => $profile->equipped_background_item_id,
            ShopItemCategory::Console->value => $profile->equipped_console_item_id,
        ]);

        $equippedKeys = ShopItem::query()->whereIn('id', $equippedIds)->pluck('key', 'id');

        return [
            'items' => $items->map(fn (ShopItem $item): array => [
                'key' => $item->key,
                'name' => $item->name,
                'category' => $item->category->value,
                'rarity' => $item->rarity,
                'description' => $item->description,
                'perk' => $item->perk,
                'price' => $item->price,
                'min_level' => $item->min_level,
                'max_quantity' => $item->max_quantity,
                'metadata' => $item->metadata,
            ])->values()->all(),
            'inventory' => $inventory,
            'equipped' => array_map(fn (int $id): ?string => $equippedKeys[$id] ?? null, $equippedIds),
        ];
    }

    /**
     * Load the student's profile with a row lock so concurrent purchases can't double-spend.
     */
    private function lockProfile(User $student): StudentProfile
    {
        return StudentProfile::query()->lockForUpdate()->find($student->id)
            ?? StudentProfile::create(['user_id' => $student->id]);
    }

    /**
     * A purchase or equip refusal shown to the student.
     */
    private function failure(string $message): ValidationException
    {
        return ValidationException::withMessages(['shop' => $message]);
    }
}
