<?php

namespace Database\Factories;

use App\Enums\ConflictType;
use App\Models\ConflictMapZone;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConflictMapZone>
 */
class ConflictMapZoneFactory extends Factory
{
    protected $model = ConflictMapZone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'conflict_type' => ConflictType::CharacterVsCharacter,
            'label' => 'CHARACTER VS CHARACTER',
            'subtitle' => 'Interpersonal conflict between individuals',
            'color_theme' => 'blue',
            'sort_order' => 1,
        ];
    }
}
