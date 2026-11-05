<?php

namespace Database\Factories;

use App\Enums\ConflictType;
use App\Models\ConflictMapEvent;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConflictMapEvent>
 */
class ConflictMapEventFactory extends Factory
{
    protected $model = ConflictMapEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'zone_id' => null,
            'card_letter' => 'Card A',
            'event_text' => fake()->sentence(),
            'target_conflict_type' => ConflictType::CharacterVsCharacter,
            'hint' => fake()->sentence(),
            'explanation' => fake()->sentence(),
            'points' => 10,
            'sort_order' => 1,
        ];
    }
}
