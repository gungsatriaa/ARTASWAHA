<?php

namespace Database\Factories;

use App\Models\ContentAudio;
use App\Models\Lesson;
use App\Services\ContentAudioService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ContentAudio> */
class ContentAudioFactory extends Factory
{
    public function definition(): array
    {
        return ['lesson_id' => Lesson::factory(), 'source_hash' => '', 'version' => (string) Str::uuid(), 'spoken_text' => 'Narasi contoh untuk pengujian.', 'language' => 'id', 'speed' => 150, 'engine' => 'upload', 'status' => 'draft'];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ContentAudio $audio): void {
            $audio->update(['source_hash' => app(ContentAudioService::class)->sourceHash($audio->lesson ?? $audio->question, $audio->lesson_audio_slot ?? 0)]);
        });
    }
}
