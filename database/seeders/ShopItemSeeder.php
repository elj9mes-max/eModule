<?php

namespace Database\Seeders;

use App\Enums\ShopItemCategory;
use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopItemSeeder extends Seeder
{
    /**
     * Stock the Gem Bazaar with purchasable HUD header backgrounds.
     *
     * Each metadata.header_skin must match a skin drawn in resources/js/Components/HudHeaderSkin.vue.
     */
    public function run(): void
    {
        $headerBackgrounds = [
            [
                'key' => 'header-sunset-meadow',
                'name' => 'Sunset Meadow',
                'rarity' => 'Common',
                'description' => 'A warm golden-hour sky with a glowing sun over rolling clay hills.',
                'perk' => 'Paints your header with a cozy sunset scene.',
                'price' => 0,
                'min_level' => 1,
                'metadata' => ['header_skin' => 'sunset-meadow', 'icon' => '🌅', 'palette' => ['border' => '#EA580C', 'soft' => '#FFF7ED', 'ledge' => '#9A3412']],
            ],
            [
                'key' => 'header-ocean-breeze',
                'name' => 'Ocean Breeze',
                'rarity' => 'Common',
                'description' => 'Rolling teal waves, a bobbing sailboat and bubbles drifting up.',
                'perk' => 'Turns your header into a breezy seaside.',
                'price' => 0,
                'min_level' => 1,
                'metadata' => ['header_skin' => 'ocean-breeze', 'icon' => '⛵', 'palette' => ['border' => '#0284C7', 'soft' => '#F0F9FF', 'ledge' => '#075985']],
            ],
            [
                'key' => 'header-candy-clouds',
                'name' => 'Candy Clouds',
                'rarity' => 'Rare',
                'description' => 'Puffy pastel clouds floating through a sky full of sprinkles.',
                'perk' => 'Sprinkles your header with sweet pastel clouds.',
                'price' => 0,
                'min_level' => 2,
                'metadata' => ['header_skin' => 'candy-clouds', 'icon' => '🍬', 'palette' => ['border' => '#DB2777', 'soft' => '#FDF2F8', 'ledge' => '#9D174D']],
            ],
            [
                'key' => 'header-starlight-night',
                'name' => 'Starlight Night',
                'rarity' => 'Rare',
                'description' => 'A crescent moon and twinkling stars above sleepy mountains.',
                'perk' => 'Gives your header a calm, starry night sky.',
                'price' => 0,
                'min_level' => 3,
                'metadata' => ['header_skin' => 'starlight-night', 'icon' => '🌙', 'palette' => ['border' => '#4F46E5', 'soft' => '#EEF2FF', 'ledge' => '#3730A3']],
            ],
            [
                'key' => 'header-aurora-sky',
                'name' => 'Aurora Sky',
                'rarity' => 'Epic',
                'description' => 'Northern lights ribbon across the sky above a quiet pine forest.',
                'perk' => 'Dancing aurora ribbons glow behind your name.',
                'price' => 0,
                'min_level' => 4,
                'metadata' => ['header_skin' => 'aurora-sky', 'icon' => '🌌', 'palette' => ['border' => '#0D9488', 'soft' => '#F0FDFA', 'ledge' => '#115E59']],
            ],
            [
                'key' => 'header-cosmic-voyage',
                'name' => 'Cosmic Voyage',
                'rarity' => 'Legendary',
                'description' => 'A rocket blasts past a ringed planet on a sparkling star trail.',
                'perk' => 'Launch your header into outer space.',
                'price' => 0,
                'min_level' => 6,
                'metadata' => ['header_skin' => 'cosmic-voyage', 'icon' => '🚀', 'palette' => ['border' => '#7C3AED', 'soft' => '#F5F3FF', 'ledge' => '#5B21B6']],
            ],
        ];

        /*
         * Expedition map trail themes. Each metadata.trail_theme must match a theme in
         * resources/js/Composables/useTrailThemes.js. Prices are 0 while testing.
         */
        $trailThemes = [
            [
                'key' => 'trail-enchanted-forest',
                'name' => 'Enchanted Forest',
                'rarity' => 'Common',
                'description' => 'Wind your quest trail through pine trees, toadstools and glowing fireflies.',
                'perk' => 'Turns your expedition map into a cozy woodland path.',
                'price' => 0,
                'min_level' => 1,
                'metadata' => ['trail_theme' => 'enchanted-forest', 'icon' => '🌲', 'palette' => ['border' => '#16A34A', 'soft' => '#F0FDF4', 'ledge' => '#166534']],
            ],
            [
                'key' => 'trail-sunny-beach',
                'name' => 'Sunny Beach',
                'rarity' => 'Common',
                'description' => 'A sandy trail past palm trees, seashells and beach umbrellas.',
                'perk' => 'Paves your expedition map with warm golden sand.',
                'price' => 0,
                'min_level' => 1,
                'metadata' => ['trail_theme' => 'sunny-beach', 'icon' => '🏝️', 'palette' => ['border' => '#D97706', 'soft' => '#FFFBEB', 'ledge' => '#92400E']],
            ],
            [
                'key' => 'trail-candy-land',
                'name' => 'Candy Land',
                'rarity' => 'Rare',
                'description' => 'Lollipops, gumdrops and candy canes line a strawberry-pink trail.',
                'perk' => 'Sprinkles your expedition map with falling candy.',
                'price' => 0,
                'min_level' => 2,
                'metadata' => ['trail_theme' => 'candy-land', 'icon' => '🍭', 'palette' => ['border' => '#DB2777', 'soft' => '#FDF2F8', 'ledge' => '#9D174D']],
            ],
            [
                'key' => 'trail-frosty-peaks',
                'name' => 'Frosty Peaks',
                'rarity' => 'Rare',
                'description' => 'An icy trail through snowy pines, snowflakes and a friendly snowman.',
                'perk' => 'Gentle snowfall drifts across your expedition map.',
                'price' => 0,
                'min_level' => 3,
                'metadata' => ['trail_theme' => 'frosty-peaks', 'icon' => '❄️', 'palette' => ['border' => '#2563EB', 'soft' => '#EFF6FF', 'ledge' => '#1E40AF']],
            ],
            [
                'key' => 'trail-space-odyssey',
                'name' => 'Space Odyssey',
                'rarity' => 'Epic',
                'description' => 'Your quest trail floats among ringed planets, rockets and comets.',
                'perk' => 'Launches your expedition map into a twinkling galaxy.',
                'price' => 0,
                'min_level' => 5,
                'metadata' => ['trail_theme' => 'space-odyssey', 'icon' => '🪐', 'palette' => ['border' => '#7C3AED', 'soft' => '#F5F3FF', 'ledge' => '#5B21B6']],
            ],
        ];

        $this->stock($headerBackgrounds, ShopItemCategory::Console);
        $this->stock($trailThemes, ShopItemCategory::Background);
    }

    /**
     * Create or refresh one-per-student cosmetic items in the given category.
     *
     * @param  list<array{key: string, name: string, rarity: string, description: string, perk: string, price: int, min_level: int, metadata: array<string, mixed>}>  $items
     */
    private function stock(array $items, ShopItemCategory $category): void
    {
        foreach ($items as $sortOrder => $item) {
            ShopItem::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    ...$item,
                    'category' => $category,
                    'max_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
