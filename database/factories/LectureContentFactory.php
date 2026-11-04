<?php

namespace Database\Factories;

use App\Enums\LectureContentType;
use App\Models\Lecture;
use App\Models\LectureContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LectureContent>
 */
class LectureContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lecture_id' => Lecture::factory(),
            'type' => LectureContentType::Text,
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(2, true),
            'file_disk' => 'public',
            'file_path' => null,
            'external_url' => null,
            'mime_type' => null,
            'file_size' => null,
            'duration_seconds' => null,
            'alt_text' => null,
            'caption' => null,
            'metadata' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * Configure the content block as an image attachment.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LectureContentType::Image,
            'content' => null,
            'file_path' => 'lectures/images/'.fake()->uuid().'.png',
            'mime_type' => 'image/png',
            'file_size' => fake()->numberBetween(120_000, 2_500_000),
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->sentence(6),
            'metadata' => ['width' => 1280, 'height' => 720],
        ]);
    }

    /**
     * Configure the content block as an audio file attachment.
     */
    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LectureContentType::Audio,
            'content' => fake()->paragraph(),
            'file_path' => 'lectures/audio/'.fake()->uuid().'.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => fake()->numberBetween(1_000_000, 8_000_000),
            'duration_seconds' => fake()->numberBetween(45, 360),
            'caption' => 'Read-along audio narration',
        ]);
    }

    /**
     * Configure the content block as a video attachment.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LectureContentType::Video,
            'content' => fake()->paragraph(),
            'file_path' => 'lectures/videos/'.fake()->uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => fake()->numberBetween(5_000_000, 45_000_000),
            'duration_seconds' => fake()->numberBetween(90, 600),
            'caption' => 'Interactive lesson video walkthrough',
        ]);
    }
}
