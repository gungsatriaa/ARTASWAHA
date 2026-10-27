<?php

namespace App\Models;

use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LearnerProfile extends Model
{
    protected $hidden = ['analysis_events'];

    protected $fillable = ['visitor_id', 'user_id', 'learning_path_id', 'learning_path_ids', 'role', 'name', 'avatar', 'mode'];

    protected function casts(): array
    {
        return [
            'learning_path_ids' => 'array',
            'analysis_events' => 'array',
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

    /** @return Builder<self> */
    public static function forAnalysis(): Builder
    {
        return self::query()->whereIn('mode', ['anonymous', 'account'])
            ->whereIn('role', array_keys(LearnerInterviewAnalyzer::ROLES))
            ->where(fn (Builder $query): Builder => $query->whereNull('user_id')->orWhereIn('id', self::query()
                ->selectRaw('MAX(id)')->whereNotNull('user_id')->groupBy('user_id')));
    }

    /** @param array<string, mixed> $details */
    public function recordAnalysisEvent(string $status, string $message, array $details = []): void
    {
        DB::transaction(function () use ($status, $message, $details): void {
            $profile = self::query()->lockForUpdate()->find($this->id);
            if ($profile === null) {
                return;
            }
            $events = $profile->analysis_events ?? [];
            $events[] = ['at' => now()->toIso8601String(), 'status' => $status, 'message' => $message, ...$details];
            $profile->forceFill(['analysis_events' => array_slice($events, -20)])->save();
        });
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }
}
