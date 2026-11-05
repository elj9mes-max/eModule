<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizContext;
use Illuminate\Database\Seeder;

class QuizContextSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Quiz::doesntHave('context')->each(function (Quiz $quiz): void {
            QuizContext::create([
                'quiz_id' => $quiz->id,
                'scenario' => $quiz->reading_passage ?: 'Read the story scenario carefully before attempting the quiz challenges.',
                'hint' => 'Focus on key narrative details and moral decisions made by the characters.',
            ]);
        });
    }
}
