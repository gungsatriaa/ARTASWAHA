<?php

namespace App\Services;

use App\Models\LearnerProfile;
use App\Models\LearningProgress;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningProgressService
{
    /** @return Collection<int, int> */
    public function completedLessonIds(string $deviceId, ?User $user = null): Collection
    {
        return LearningProgress::query()->whereNotNull('completed_at')
            ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
            ->when($user === null, fn (Builder $query): Builder => $query->where('visitor_id', $deviceId)->whereNull('user_id'))
            ->pluck('lesson_id');
    }

    public function prerequisite(Lesson $lesson, string $deviceId, ?User $user = null): ?Lesson
    {
        return Lesson::query()->where('learning_path_id', $lesson->learning_path_id)
            ->where('is_published', true)->whereNotNull('reviewed_at')
            ->where(fn (Builder $query): Builder => $query->where('sort_order', '<', $lesson->sort_order)
                ->orWhere(fn (Builder $query): Builder => $query->where('sort_order', $lesson->sort_order)->where('id', '<', $lesson->id)))
            ->whereNotIn('id', $this->completedLessonIds($deviceId, $user))
            ->orderBy('sort_order')->orderBy('id')->first(['id', 'title', 'slug']);
    }

    public function deviceId(Request $request): string
    {
        $deviceId = $request->cookie('artaswaha_device');

        return is_string($deviceId) && Str::isUuid($deviceId) ? $deviceId : (string) Str::uuid();
    }

    public function completeLesson(string $deviceId, Lesson $lesson, ?User $user = null): LearningProgress
    {
        return DB::transaction(fn (): LearningProgress => LearningProgress::query()->firstOrCreate(
            ['visitor_id' => $deviceId, 'lesson_id' => $lesson->id],
            ['user_id' => $user?->id, 'points' => 10, 'completed_at' => now()],
        ));
    }

    public function answerExercise(string $deviceId, Lesson $lesson, int|array $answerIndex, ?User $user = null): bool
    {
        if (LearningProgress::query()->where('visitor_id', $deviceId)->where('lesson_id', $lesson->id)->exists()) {
            return true;
        }

        if (! app(QuestionAnswerService::class)->isCorrect($lesson, is_array($answerIndex) ? $answerIndex : ['answer_index' => $answerIndex])) {
            return false;
        }

        $this->completeLesson($deviceId, $lesson, $user);

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
