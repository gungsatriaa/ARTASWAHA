<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningProgressRequest;
use App\Http\Requests\StoreLessonExerciseAnswerRequest;
use App\Models\Lesson;
use App\Services\LearningProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LearningProgressController extends Controller
{
    public function store(StoreLearningProgressRequest $request, Lesson $lesson, LearningProgressService $progressService): RedirectResponse
    {
        $this->ensureAvailable($lesson);
        abort_if($lesson->exercise_prompt !== null, 403, 'Jawab latihan sebelum menyelesaikan pelajaran.');

        $deviceId = $progressService->deviceId($request);
        $progressService->completeLesson($deviceId, $lesson);

        return to_route('learning.lessons.show', $lesson)
            ->with('status', 'Pelajaran selesai! Kamu mendapat 10 poin.')
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    public function answerExercise(StoreLessonExerciseAnswerRequest $request, Lesson $lesson, LearningProgressService $progressService): RedirectResponse
    {
        $this->ensureAvailable($lesson);
        abort_if($lesson->exercise_prompt === null, 404);

        $deviceId = $progressService->deviceId($request);
        $isCorrect = $progressService->answerExercise($deviceId, $lesson, (int) $request->validated('answer_index'));

        return to_route('learning.lessons.show', $lesson)
            ->with('status', $isCorrect ? 'Benar! Pelajaran selesai dan 10 poin tersimpan.' : 'Belum tepat. Coba ingat lagi materi di atas, lalu pilih jawaban lain.')
            ->with('status_tone', $isCorrect ? 'success' : 'info')
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    private function ensureAvailable(Lesson $lesson): void
    {
        $lesson->loadMissing('learningPath:id,is_published');
        abort_unless($lesson->is_published && $lesson->reviewed_at !== null && $lesson->learningPath->is_published, 404);
    }
}
