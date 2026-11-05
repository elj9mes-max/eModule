<?php

namespace Database\Factories;

use App\Models\EvaluationItem;
use App\Models\EvaluationItemOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationItemOption>
 */
class EvaluationItemOptionFactory extends Factory
{
    protected $model = EvaluationItemOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => EvaluationItem::factory(),
            'option_text' => fake()->words(3, true),
            'is_correct' => true,
            'sort_order' => 1,
        ];
    }
}
