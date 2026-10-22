<?php

namespace Database\Factories;

use App\Models\QuizQuestion;
use App\Models\RoomAnswer;
use App\Models\RoomParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomAnswer>
 */
class RoomAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_participant_id' => RoomParticipant::factory(),
            'quiz_question_id' => QuizQuestion::factory(),
            'answer_index' => 0,
            'is_correct' => true,
            'points_awarded' => 10,
            'answered_at' => now(),
        ];
    }
}
