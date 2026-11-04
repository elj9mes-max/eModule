<?php

namespace Database\Seeders;

use App\Enums\LectureContentType;
use App\Enums\ModuleStatus;
use App\Enums\ModuleTheme;
use App\Enums\UserRole;
use App\Models\Lecture;
use App\Models\LectureContent;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Master Instructor',
                'password' => 'password',
                'role' => UserRole::Teacher,
                'email_verified_at' => now(),
            ]
        );

        $modulesData = [
            [
                'title' => 'Whispering Forest',
                'grade_level' => 'Grade 3 • Reading Legends',
                'description' => 'Explore phonics and enchanted woodland fables through interactive storytelling.',
                'theme' => ModuleTheme::Forest,
                'icon' => '🌲',
                'xp_bonus' => 250,
                'boss_name' => 'Grammar Goblin',
                'max_students' => 35,
            ],
            [
                'title' => 'Sunken Atlantis',
                'grade_level' => 'Grade 4 • Ocean Comprehension',
                'description' => 'Dive deep into ocean comprehension, vocabulary treasures, and coral mysteries.',
                'theme' => ModuleTheme::Ocean,
                'icon' => '🌊',
                'xp_bonus' => 300,
                'boss_name' => 'Kraken of Syntax',
                'max_students' => 30,
            ],
            [
                'title' => 'Cosmic Nebula',
                'grade_level' => 'Grade 5 • Sci-Fi Writers Guild',
                'description' => 'Journey across star systems while crafting imaginative sci-fi narratives.',
                'theme' => ModuleTheme::Cosmic,
                'icon' => '🚀',
                'xp_bonus' => 400,
                'boss_name' => 'Asteroid Dragon',
                'max_students' => 32,
            ],
            [
                'title' => 'Dino Volcanoes',
                'grade_level' => 'Grade 2 • Story Detectives',
                'description' => 'Uncover prehistoric clues, rhyming fossils, and fast-paced reading quests.',
                'theme' => ModuleTheme::Volcano,
                'icon' => '🌋',
                'xp_bonus' => 200,
                'boss_name' => 'T-Rex Rhyme King',
                'max_students' => 30,
            ],
        ];

        foreach ($modulesData as $index => $moduleData) {
            $module = Module::updateOrCreate(
                ['slug' => Str::slug($moduleData['title'])],
                [
                    'user_id' => $teacher->id,
                    'title' => $moduleData['title'],
                    'grade_level' => $moduleData['grade_level'],
                    'description' => $moduleData['description'],
                    'theme' => $moduleData['theme'],
                    'icon' => $moduleData['icon'],
                    'xp_bonus' => $moduleData['xp_bonus'],
                    'boss_name' => $moduleData['boss_name'],
                    'max_students' => $moduleData['max_students'],
                    'status' => ModuleStatus::Published,
                    'sort_order' => $index + 1,
                    'published_at' => now(),
                ]
            );

            $lecture = Lecture::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'slug' => 'chapter-1-welcome-to-'.Str::slug($moduleData['title']),
                ],
                [
                    'title' => 'Chapter 1: Welcome to '.$moduleData['title'],
                    'description' => 'Introduction lecture covering core reading concepts, audio pronunciation, and visual story aids.',
                    'body' => '<p>Welcome adventurers! Read through the passage below, listen to the narration audio, and watch the animated story guide.</p>',
                    'estimated_duration_minutes' => 15,
                    'xp_reward' => 150,
                    'sort_order' => 1,
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );

            LectureContent::updateOrCreate(
                [
                    'lecture_id' => $lecture->id,
                    'sort_order' => 1,
                ],
                [
                    'type' => LectureContentType::Text,
                    'title' => 'Story Passage & Key Vocabulary',
                    'content' => 'In the heart of '.$moduleData['title'].', young readers discover the power of context clues and expressive reading.',
                ]
            );

            LectureContent::updateOrCreate(
                [
                    'lecture_id' => $lecture->id,
                    'sort_order' => 2,
                ],
                [
                    'type' => LectureContentType::Audio,
                    'title' => 'Read-Along Audio Narration',
                    'content' => 'Listen carefully to the pronunciation of each highlighted vocabulary word.',
                    'file_disk' => 'public',
                    'file_path' => 'lectures/audio/sample-narration.mp3',
                    'mime_type' => 'audio/mpeg',
                    'duration_seconds' => 120,
                    'caption' => 'Teacher voiceover read-along',
                ]
            );
        }
    }
}
