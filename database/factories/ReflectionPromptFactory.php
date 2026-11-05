<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\ReflectionPrompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReflectionPrompt>
 */
class ReflectionPromptFactory extends Factory
{
    protected $model = ReflectionPrompt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'prompt_text' => fake()->paragraph(),
            'max_words' => 150,
            'min_words' => 20,
            'guiding_hint' => fake()->sentence(),
            'starter_sentence' => 'In my opinion, the story shows that...',
            'points' => 50,
            'sort_order' => 1,
        ];
    }
}
