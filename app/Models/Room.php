<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = ['quiz_id', 'host_id', 'code', 'status', 'leaderboard_enabled', 'current_question_id', 'started_at', 'finished_at', 'seconds_per_question', 'question_ends_at'];

    protected function casts(): array
    {
        return ['leaderboard_enabled' => 'boolean', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'seconds_per_question' => 'integer', 'question_ends_at' => 'datetime'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'current_question_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RoomParticipant::class)->orderByDesc('score')->orderBy('joined_at');
    }
}
