<?php

namespace Database\Factories;

use App\Models\EvaluationWordHelper;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationWordHelper>
 */
class EvaluationWordHelperFactory extends Factory
{
    protected $model = EvaluationWordHelper::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'word' => fake()->word(),
            'sort_order' => 1,
        ];
    }
}
