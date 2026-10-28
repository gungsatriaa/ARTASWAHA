<?php

namespace App\Models;

use Database\Factories\LessonAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['questions', 'answers', 'visitor_id'])]
class LessonAttempt extends Model
{
    /** @use HasFactory<LessonAttemptFactory> */
    use HasFactory;

    protected $fillable = ['lesson_id', 'user_id', 'visitor_id', 'version', 'questions', 'answers', 'completed_at'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'answers' => 'array', 'completed_at' => 'datetime'];
    }

    public function scopeForLearner(Builder $query, string $deviceId, ?int $userId): void
    {
        $userId === null ? $query->where('visitor_id', $deviceId)->whereNull('user_id') : $query->where('user_id', $userId);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
