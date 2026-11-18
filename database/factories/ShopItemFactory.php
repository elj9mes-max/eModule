<?php

namespace Database\Factories;

use App\Enums\ShopItemCategory;
use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopItem>
 */
class ShopItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'category' => ShopItemCategory::Avatar,
            'rarity' => 'Common',
            'description' => fake()->sentence(),
            'perk' => fake()->sentence(),
            'price' => 100,
            'min_level' => 1,
            'max_quantity' => 1,
            'is_active' => true,
            'sort_order' => 0,
            'metadata' => null,
        ];
    }

    /**
     * A stackable quiz booster.
     */
    public function booster(int $maxQuantity = 3): static
    {
        return $this->state(fn (): array => [
            'category' => ShopItemCategory::Booster,
            'price' => 25,
            'max_quantity' => $maxQuantity,
        ]);
    }

    /**
     * An item that is no longer on sale.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
