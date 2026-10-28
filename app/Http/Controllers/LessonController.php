<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\ContentAudioService;
use App\Services\LearningProgressService;
use App\Services\PagePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson, LearningProgressService $progressService, ContentAudioService $audioService): Response
    {
        $lesson->loadMissing('learningPath:id,slug,title,group,is_published');
        abort_unless($lesson->is_published && $lesson->reviewed_at !== null && $lesson->learningPath->is_published, 404);

        $deviceId = $progressService->deviceId($request);
        $prerequisite = $progressService->prerequisite($lesson, $deviceId, $request->user());
        if ($prerequisite !== null) {
            return to_route('learning.paths.show', $lesson->learningPath->slug)
                ->with('status', 'Selesaikan modul “'.$prerequisite->title.'” terlebih dahulu untuk membuka modul ini.')
                ->with('status_tone', 'info');
        }
        $progress = $lesson->progress()
            ->when($request->user() !== null, fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->user() === null, fn ($query) => $query->where('visitor_id', $deviceId)->whereNull('user_id'))->whereNotNull('completed_at')->first();
        $audio = $audioService->forContent($lesson);
        $audio = $audioService->isPlayable($audio) ? $audio : null;

        return app(PagePresenter::class)->render('learning.lessons.show', compact('lesson', 'progress', 'audio'))->toResponse($request)
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
