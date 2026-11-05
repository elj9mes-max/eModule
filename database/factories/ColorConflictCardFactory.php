<?php

namespace Database\Factories;

use App\Enums\ConflictType;
use App\Models\ColorConflictCard;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColorConflictCard>
 */
class ColorConflictCardFactory extends Factory
{
    protected $model = ColorConflictCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'excerpt' => fake()->paragraph(),
            'conflict_type' => fake()->randomElement(ConflictType::cases()),
            'hint' => fake()->sentence(),
            'explanation' => fake()->sentence(),
            'feedback_incorrect' => fake()->sentence(),
            'points' => 10,
            'sort_order' => 1,
        ];
    }
}
