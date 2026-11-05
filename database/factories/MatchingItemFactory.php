<?php

namespace Database\Factories;

use App\Models\MatchingChoice;
use App\Models\MatchingItem;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchingItem>
 */
class MatchingItemFactory extends Factory
{
    protected $model = MatchingItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'correct_choice_id' => MatchingChoice::factory(),
            'item_number' => 1,
            'description' => fake()->sentence(),
            'hint' => fake()->sentence(),
            'explanation' => fake()->sentence(),
            'points' => 10,
            'sort_order' => 1,
        ];
    }
}
