<?php

namespace Database\Factories;

use App\Enums\QuizType;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'module_id' => Module::factory(),
            'lecture_id' => null,
            'type' => QuizType::MultipleChoice,
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->paragraph(),
            'reading_passage' => fake()->paragraphs(2, true),
            'xp_reward' => fake()->randomElement([100, 150, 200, 250]),
            'passing_percentage' => 70,
            'time_limit_seconds' => null,
            'sort_order' => 1,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
