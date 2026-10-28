<?php

namespace App\Services;

use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningProgress;
use App\Models\LessonAttempt;
use App\Models\QuizDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AccountDeletionService
{
    public function delete(User|LearnerProfile $identity, string $sessionId): bool
    {
        $files = DB::transaction(function () use ($identity, $sessionId): array {
            $identity = $identity instanceof User
                ? User::query()->whereKey($identity->id)->lockForUpdate()->firstOrFail()
                : LearnerProfile::query()->whereKey($identity->id)->whereNull('user_id')->where('mode', 'anonymous')->lockForUpdate()->firstOrFail();
            $avatars = collect([$identity->avatar]);
            $files = collect();
            if ($identity instanceof User) {
                $profiles = LearnerProfile::query()->where('user_id', $identity->id)->lockForUpdate()->get();
                $avatars = $avatars->merge($profiles->pluck('avatar'));
                $files = QuizDraft::query()->where('user_id', $identity->id)->pluck('source_path')
                    ->merge(ContentAudio::query()->whereHas('question.quiz', fn (Builder $query): Builder => $query->where('user_id', $identity->id))->pluck('path'));
                LearningProgress::query()->where('user_id', $identity->id)->delete();
                LearningProgress::query()->whereIn('visitor_id', $profiles->pluck('visitor_id'))->whereNull('user_id')->delete();
                LessonAttempt::query()->whereIn('visitor_id', $profiles->pluck('visitor_id'))->whereNull('user_id')->delete();
                LearnerProfile::query()->where('user_id', $identity->id)->delete();
                DB::table('sessions')->where('user_id', $identity->id)->delete();
                Password::broker()->deleteToken($identity);
                DB::table('active_visitors')->where('visitor_key', hash('sha256', 'user:'.$identity->id))->delete();
            } else {
                LearningProgress::query()->where('visitor_id', $identity->visitor_id)->whereNull('user_id')->delete();
                LessonAttempt::query()->forLearner($identity->visitor_id, null)->delete();
            }
            DB::table('active_visitors')->where('visitor_key', hash('sha256', 'session:'.$sessionId))->delete();
            $identity->delete();

            return $files->merge($avatars->filter(fn (?string $path): bool => str_starts_with($path ?? '', 'avatars/')))
                ->filter()->unique()->values()->all();
        });

        $filesDeleted = true;
        foreach ($files as $file) {
            try {
                if (! Storage::disk('local')->delete($file)) {
                    $filesDeleted = false;
                    Log::warning('Account deletion file cleanup failed.', ['path' => $file]);
                }
            } catch (Throwable $exception) {
                $filesDeleted = false;
                report($exception);
                Log::warning('Account deletion file cleanup failed.', ['path' => $file]);
            }
        }

        return $filesDeleted;
    }
}
