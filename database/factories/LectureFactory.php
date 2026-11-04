<?php

namespace Database\Factories;

use App\Models\Lecture;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lecture>
 */
class LectureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'module_id' => Module::factory(),
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'estimated_duration_minutes' => fake()->numberBetween(10, 45),
            'xp_reward' => fake()->randomElement([100, 150, 200, 250]),
            'sort_order' => 0,
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the lecture is unpublished.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
