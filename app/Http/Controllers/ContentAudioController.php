<?php

namespace App\Http\Controllers;

use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningProgress;
use App\Models\Room;
use App\Services\ContentAudioService;
use App\Services\LearningProgressService;
use App\Services\LessonAttemptService;
use App\Services\RoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContentAudioController extends Controller
{
    public function __invoke(Request $request, ContentAudio $contentAudio, ContentAudioService $service, RoomService $rooms): BinaryFileResponse
    {
        $request->validate(['room' => ['nullable', 'string', 'max:12'], 'feedback' => ['nullable', 'integer']]);
        $contentAudio->load(['lesson.learningPath', 'question.quiz']);
        abort_unless($contentAudio->path !== null && str_starts_with($contentAudio->path, 'content-audio/') && Storage::disk('local')->exists($contentAudio->path), 404);
        if (! $request->user()?->is_admin) {
            abort_unless($service->isPlayable($contentAudio), 404);
            if ($contentAudio->lesson !== null) {
                $lesson = $contentAudio->lesson;
                abort_unless($lesson->is_published && $lesson->reviewed_at !== null && $lesson->learningPath->is_published, 404);
                $progress = app(LearningProgressService::class);
                abort_if($progress->prerequisite($lesson, $progress->deviceId($request), $request->user()) !== null, 403, 'Selesaikan modul sebelumnya terlebih dahulu.');
                if ($contentAudio->lesson_audio_slot > 0) {
                    $deviceId = $progress->deviceId($request);
                    $completed = LearningProgress::query()->forLearner($deviceId, $request->user()?->id)->where('lesson_id', $lesson->id)->whereNotNull('completed_at')->first();
                    $quiz = app(LessonAttemptService::class)->presentation($lesson, $completed, $deviceId, $request->user());
                    abort_unless($quiz !== null && $quiz['question'] !== null && $contentAudio->lesson_audio_slot === $quiz['index'] + 1, 404);
                }
            } else {
                $question = $contentAudio->question;
                abort_if($question === null, 404);
                if ($question->quiz->user_id !== $request->user()?->id) {
                    $player = $request->user() ?? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device'));
                    abort_if($player === null, 404);
                    $room = Room::where('code', $request->query('room'))->firstOrFail();
                    $data = $rooms->viewData($room, $player);
                    abort_unless($data['activeQuestion']?->id === $question->id, 404);
                }
            }
        }

        return response()->file(Storage::disk('local')->path($contentAudio->path), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }
}
