<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningProgressRequest;
use App\Http\Requests\StoreLessonExerciseAnswerRequest;
use App\Models\Lesson;
use App\Services\LearningProgressService;
use App\Services\LessonAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LearningProgressController extends Controller
{
    public function store(StoreLearningProgressRequest $request, Lesson $lesson, LearningProgressService $progressService): RedirectResponse
    {
        $this->ensureAvailable($lesson);
        abort_if($lesson->exercise_prompt !== null || ! empty($lesson->exercises), 403, 'Jawab latihan sebelum menyelesaikan pelajaran.');

        $deviceId = $progressService->deviceId($request);
        abort_if($progressService->prerequisite($lesson, $deviceId, $request->user()) !== null, 403, 'Selesaikan modul sebelumnya terlebih dahulu.');
        $progressService->completeLesson($deviceId, $lesson, $request->user());

        return to_route('learning.lessons.show', $lesson)
            ->with('status', 'Pelajaran selesai! Kamu mendapat 10 poin.')
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    public function answerExercise(StoreLessonExerciseAnswerRequest $request, Lesson $lesson, LearningProgressService $progressService): RedirectResponse
    {
        $this->ensureAvailable($lesson);
        abort_if($lesson->exercise_prompt === null && empty($lesson->exercises), 404);

        $deviceId = $progressService->deviceId($request);
        abort_if($progressService->prerequisite($lesson, $deviceId, $request->user()) !== null, 403, 'Selesaikan modul sebelumnya terlebih dahulu.');
        app(LessonAttemptService::class)->answer($lesson, $deviceId, $request->user(), $request->validated());

        return to_route('learning.lessons.show', $lesson)
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    public function retry(Request $request, Lesson $lesson, LearningProgressService $progressService, LessonAttemptService $attempts): RedirectResponse
    {
        $this->ensureAvailable($lesson);
        abort_if($lesson->exerciseQuestions() === [], 404);
        $deviceId = $progressService->deviceId($request);
        abort_if($progressService->prerequisite($lesson, $deviceId, $request->user()) !== null, 403);
        $data = $request->validate(['attempt_id' => ['nullable', 'integer', 'min:1']]);
        $attempts->retry($lesson, $deviceId, $request->user(), isset($data['attempt_id']) ? (int) $data['attempt_id'] : null);

        return to_route('learning.lessons.show', $lesson)
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    private function ensureAvailable(Lesson $lesson): void
    {
        $lesson->loadMissing('learningPath:id,is_published');
        abort_unless($lesson->is_published && $lesson->reviewed_at !== null && $lesson->learningPath->is_published, 404);
    }
}
