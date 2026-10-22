<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use Illuminate\Database\Seeder;

class LearningPathSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paths = [
            ['group' => 'paribasa', 'title' => 'Cecimpedan', 'slug' => 'cecimpedan', 'description' => 'Teka-teki tradisional untuk mengasah rasa ingin tahu.', 'sort_order' => 1],
            ['group' => 'paribasa', 'title' => 'Peparikan', 'slug' => 'peparikan', 'description' => 'Belajar parikan dan permainan bunyi dalam Bahasa Bali.', 'sort_order' => 2],
            ['group' => 'paribasa', 'title' => 'Sesonggakan', 'slug' => 'sesonggakan', 'description' => 'Mengenal ungkapan dan perbandingan dalam tradisi Bali.', 'sort_order' => 3],
            ['group' => 'aksara', 'title' => 'Aksara Wreastra & Gantungan', 'slug' => 'wreastra-gantungan', 'description' => 'Mengenal aksara dasar dan bentuk gantungannya.', 'sort_order' => 1],
            ['group' => 'aksara', 'title' => 'Pengangge Suara', 'slug' => 'pengangge-suara', 'description' => 'Mengenal tanda vokal pada Aksara Bali.', 'sort_order' => 2],
            ['group' => 'aksara', 'title' => 'Pengangge Tengenan', 'slug' => 'pengangge-tengenan', 'description' => 'Mengenal tanda konsonan akhir dalam Aksara Bali.', 'sort_order' => 3],
            ['group' => 'aksara', 'title' => 'Pengangge Arda Suara', 'slug' => 'pengangge-arda-suara', 'description' => 'Mempelajari tanda aksara sesuai materi yang ditinjau.', 'sort_order' => 4],
            ['group' => 'aksara', 'title' => 'Angka Aksara Bali', 'slug' => 'angka-aksara-bali', 'description' => 'Mengenal angka dalam Aksara Bali.', 'sort_order' => 5],
        ];

        foreach ($paths as $path) {
            LearningPath::query()->updateOrCreate(['slug' => $path['slug']], [...$path, 'is_published' => true]);
        }
    }
}
