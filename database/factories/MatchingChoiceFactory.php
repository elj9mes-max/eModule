<?php

namespace Database\Factories;

use App\Models\MatchingChoice;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchingChoice>
 */
class MatchingChoiceFactory extends Factory
{
    protected $model = MatchingChoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'choice_letter' => 'A',
            'choice_text' => fake()->words(3, true),
            'sort_order' => 1,
        ];
    }
}
