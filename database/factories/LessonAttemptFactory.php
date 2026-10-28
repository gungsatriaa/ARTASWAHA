<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<LessonAttempt> */
class LessonAttemptFactory extends Factory
{
    public function definition(): array
    {
        return ['lesson_id' => Lesson::factory(), 'user_id' => null, 'visitor_id' => (string) Str::uuid(),
            'version' => hash('sha256', 'test'), 'questions' => [], 'answers' => [], 'completed_at' => null];
    }
}
