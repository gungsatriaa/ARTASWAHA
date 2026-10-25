<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerProfile extends Model
{
    protected $fillable = ['visitor_id', 'user_id', 'learning_path_id', 'learning_path_ids', 'role', 'name', 'avatar', 'mode'];

    protected function casts(): array
    {
        return [
            'learning_path_ids' => 'array',
            'analysis_input' => 'array',
            'analysis_result' => 'array',
            'analysis_requested_at' => 'datetime',
            'analysis_completed_at' => 'datetime',
        ];
    }

    public static function completedFor(?int $userId, mixed $visitorId): ?self
    {
        if ($userId !== null) {
            return self::query()->where('user_id', $userId)->latest('id')->first();
        }

        if (! is_string($visitorId) || $visitorId === '') {
            return null;
        }

        return self::query()
            ->where('visitor_id', $visitorId)
            ->where('mode', 'anonymous')
            ->whereNull('user_id')
            ->first();
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }
}
