<?php

namespace Database\Seeders;

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
        $this->call([
            LearningPathSeeder::class,
            AdminUserSeeder::class,
            ProductionAdminSeeder::class,
            DemoLessonSeeder::class,
            AksaraBaliLessonSeeder::class,
            ParibasaLessonSeeder::class,
            ContentAudioSeeder::class,
            AiReportSeeder::class,
        ]);
    }
}
