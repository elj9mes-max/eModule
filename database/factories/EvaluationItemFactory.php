<?php

namespace Database\Factories;

use App\Models\EvaluationItem;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationItem>
 */
class EvaluationItemFactory extends Factory
{
    protected $model = EvaluationItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'section_title' => '1. EVALUATING CHARACTER VS. CHARACTER',
            'conflict_type' => 'character_vs_character',
            'question_prompt' => fake()->sentence(),
            'step1_prompt' => 'Step 1: Pick one option:',
            'sentence_template' => 'Aling Marta was [___] because she should have [___].',
            'hint' => fake()->sentence(),
            'points' => 25,
            'sort_order' => 1,
        ];
    }
}
