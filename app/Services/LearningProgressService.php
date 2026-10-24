<?php

namespace App\Services;

use App\Models\LearnerProfile;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function answerExercise(string $deviceId, Lesson $lesson, int $answerIndex): bool
    {
        if (LearningProgress::query()->where('visitor_id', $deviceId)->where('lesson_id', $lesson->id)->exists()) {
            return true;
        }

        abort_unless(is_array($lesson->exercise_options) && array_key_exists($answerIndex, $lesson->exercise_options), 422);

        if ($answerIndex !== $lesson->correct_option) {
            return false;
        }

        $this->completeLesson($deviceId, $lesson);

        return true;
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

        $profile = LearnerProfile::query()
            ->where('visitor_id', $deviceId)
            ->whereNull('user_id')
            ->first();

        if ($profile !== null) {
            $previousAvatar = $profile->avatar;
            $profile->update(['user_id' => $user->id, 'name' => $user->name, 'avatar' => $user->avatar, 'mode' => 'account']);

            if ($previousAvatar !== $user->avatar && str_starts_with($previousAvatar ?? '', 'avatars/')) {
                Storage::disk('local')->delete($previousAvatar);
            }
        }
    }
}
