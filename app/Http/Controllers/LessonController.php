<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson, LearningProgressService $progressService): Response
    {
        abort_unless($lesson->is_published && $lesson->reviewed_at !== null, 404);

        $deviceId = $progressService->deviceId($request);
        $progress = $lesson->progress()->where('visitor_id', $deviceId)->first();

        return response()
            ->view('learning.lessons.show', compact('lesson', 'progress'))
            ->withCookie(Cookie::make('artaswaha_device', $deviceId, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
