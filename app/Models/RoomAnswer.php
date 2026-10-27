<?php

namespace App\Models;

use Database\Factories\RoomAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAnswer extends Model
{
    /** @use HasFactory<RoomAnswerFactory> */
    use HasFactory;

    protected $fillable = ['answer_indexes', 'answer_text', 'room_participant_id', 'quiz_question_id', 'answer_index', 'is_correct', 'points_awarded', 'answered_at'];

    protected function casts(): array
    {
        return ['answer_indexes' => 'array', 'answer_index' => 'integer', 'is_correct' => 'boolean', 'answered_at' => 'datetime'];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(RoomParticipant::class, 'room_participant_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
