<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerProfile extends Model
{
    protected $fillable = ['visitor_id', 'user_id', 'learning_path_id', 'role', 'name', 'avatar', 'mode'];

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
