<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Services\ContentAudioService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ContentAudioSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ContentAudioService::class);
        Lesson::whereDoesntHave('audio')->where('is_published', true)->chunkById(100, function (Collection $lessons) use ($service): void {
            foreach ($lessons as $lesson) {
                $service->save($lesson, ['spoken_text' => $service->defaultText($lesson), 'language' => 'id', 'speed' => 150, 'action' => 'save']);
            }
        });
    }
}
