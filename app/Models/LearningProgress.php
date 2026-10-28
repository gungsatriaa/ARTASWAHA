<?php

namespace App\Models;

use Database\Factories\LearningProgressFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningProgress extends Model
{
    /** @use HasFactory<LearningProgressFactory> */
    use HasFactory;

    protected $fillable = ['visitor_id', 'user_id', 'lesson_id', 'points', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function scopeForLearner(Builder $query, string $deviceId, ?int $userId): void
    {
        $userId === null ? $query->where('visitor_id', $deviceId)->whereNull('user_id') : $query->where('user_id', $userId);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
