<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningProgressRequest;
use App\Http\Requests\StoreLessonExerciseAnswerRequest;
use App\Models\Lesson;
use App\Services\LearningProgressService;
use App\Services\QuestionAnswerService;
use Illuminate\Http\RedirectResponse;
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
        if (! empty($lesson->exercises)) {
            $index = (int) $request->session()->get($lesson->exerciseSessionKey(), 0);
            abort_unless($request->integer('question_index', -1) === $index && isset($lesson->exercises[$index]), 409, 'Soal sudah berubah. Muat ulang halaman sebelum menjawab.');
            $exercise = new Lesson($lesson->exercises[$index]);
            $isCorrect = app(QuestionAnswerService::class)->isCorrect($exercise, $request->validated());
            if ($isCorrect) {
                $request->session()->put($lesson->exerciseSessionKey(), ++$index);
                if ($index === count($lesson->exercises)) {
                    $progressService->completeLesson($deviceId, $lesson, $request->user());
                }
            }

            return to_route('learning.lessons.show', $lesson)
                ->with('lesson_feedback', ['correct' => $isCorrect, 'question_index' => $index - ($isCorrect ? 1 : 0), 'explanation' => $exercise->exercise_explanation, 'correct_answer' => app(QuestionAnswerService::class)->correctText($exercise)])
                ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
        }
        $isCorrect = $progressService->answerExercise($deviceId, $lesson, $request->validated(), $request->user());

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
