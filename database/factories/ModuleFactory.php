<?php

namespace Database\Factories;

use App\Enums\ModuleStatus;
use App\Enums\ModuleTheme;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'user_id' => User::factory()->teacher(),
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'grade_level' => 'Grade '.fake()->numberBetween(1, 6).' • Reading Legends',
            'description' => fake()->paragraph(),
            'theme' => fake()->randomElement(ModuleTheme::cases()),
            'icon' => fake()->randomElement(['🌲', '🌊', '🚀', '🌋', '📘']),
            'cover_image_path' => null,
            'xp_bonus' => fake()->randomElement([150, 200, 250, 300, 400]),
            'boss_name' => fake()->randomElement(['Grammar Goblin', 'Kraken of Syntax', 'Asteroid Dragon', 'T-Rex Rhyme King']),
            'max_students' => 35,
            'status' => ModuleStatus::Published,
            'sort_order' => 0,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the module is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModuleStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the module is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModuleStatus::Published,
            'published_at' => now(),
        ]);
    }
}
