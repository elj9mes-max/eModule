<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\ModuleBonusClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleBonusClaim>
 */
class ModuleBonusClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'module_id' => Module::factory(),
            'xp_awarded' => fake()->randomElement([250, 300, 500]),
            'claimed_at' => now(),
        ];
    }
}
