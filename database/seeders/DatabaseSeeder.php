<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->student()->create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
        ]);

        User::factory()->teacher()->create([
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
        ]);
    }
}
