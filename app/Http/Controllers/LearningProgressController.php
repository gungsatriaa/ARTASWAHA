<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningProgressRequest;
use App\Models\Lesson;
use App\Services\LearningProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LearningProgressController extends Controller
{
    public function store(StoreLearningProgressRequest $request, Lesson $lesson, LearningProgressService $progressService): RedirectResponse
    {
        abort_unless($lesson->is_published && $lesson->reviewed_at !== null, 404);

        $deviceId = $progressService->deviceId($request);
        $progressService->completeLesson($deviceId, $lesson);

        return to_route('learning.lessons.show', $lesson)
            ->with('status', 'Pelajaran selesai! Kamu mendapat 10 poin.')
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
