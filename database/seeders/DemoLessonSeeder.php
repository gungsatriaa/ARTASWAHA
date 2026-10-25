<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class DemoLessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = LearningPath::query()->firstOrCreate(['slug' => 'cecimpedan'], [
            'group' => 'paribasa',
            'title' => 'Cecimpedan',
            'description' => 'Mengenal teka-teki tradisional Bali.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::query()->firstOrCreate(['slug' => 'demo-mengenal-cecimpedan'], [
            'learning_path_id' => $path->id,
            'title' => 'Mengenal Cecimpedan (Demo)',
            'summary' => 'Materi singkat untuk mencoba membaca, latihan, dan audio.',
            'content' => "Cecimpedan adalah teka-teki dalam tradisi lisan Bali. Kita mendengarkan petunjuk, lalu menebak jawabannya.\n\nContoh latihan sederhana: Memiliki gigi, tetapi tidak bisa menggigit. Apakah itu? Jawabannya adalah sisir.\n\nSaat menjawab, perhatikan ciri benda dan pikirkan kegunaannya. Cobalah membuat satu teka-teki tentang benda di sekitarmu.",
            'sort_order' => 1,
            'is_published' => true,
            'reviewed_at' => now(),
            'exercise_prompt' => 'Apa tujuan utama bermain cecimpedan?',
            'exercise_options' => ['Menebak jawaban berdasarkan petunjuk', 'Menghafal angka', 'Menyalin tulisan', 'Menghitung benda'],
            'correct_option' => 0,
            'exercise_explanation' => 'Cecimpedan melatih kita memahami petunjuk dan menemukan jawabannya.',
        ]);
    }
}
