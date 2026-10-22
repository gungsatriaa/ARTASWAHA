<?php

namespace App\Services;

use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningProgressService
{
    public function deviceId(Request $request): string
    {
        $deviceId = $request->cookie('artaswaha_device');

        return is_string($deviceId) && Str::isUuid($deviceId) ? $deviceId : (string) Str::uuid();
    }

    public function completeLesson(string $deviceId, Lesson $lesson): LearningProgress
    {
        return DB::transaction(fn (): LearningProgress => LearningProgress::query()->firstOrCreate(
            ['visitor_id' => $deviceId, 'lesson_id' => $lesson->id],
            ['points' => 10, 'completed_at' => now()],
        ));
    }

    public function claimDeviceProgress(Request $request, User $user): void
    {
        $deviceId = $request->cookie('artaswaha_device');

        if (! is_string($deviceId) || ! Str::isUuid($deviceId)) {
            return;
        }

        LearningProgress::query()
            ->where('visitor_id', $deviceId)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }
}
