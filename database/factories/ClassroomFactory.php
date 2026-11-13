<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adjectives = ['Emerald', 'Ruby', 'Sapphire', 'Diamond', 'Orion', 'Phoenix', 'Star', 'Golden'];
        $grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $grade = fake()->randomElement($grades);
        $section = fake()->randomElement($adjectives);

        return [
            'user_id' => User::factory()->teacher(),
            'name' => "{$grade} - Section {$section}",
            'grade_level' => "{$grade} • Reading Legends",
            'class_code' => Classroom::generateUniqueClassCode(),
            'theme' => fake()->randomElement(['forest', 'ocean', 'cosmic', 'volcano']),
            'icon' => fake()->randomElement(['🏫', '🌲', '🚀', '🌊', '⭐', '🏰']),
            'description' => fake()->sentence(8),
            'max_students' => 45,
            'is_active' => true,
        ];
    }
}
