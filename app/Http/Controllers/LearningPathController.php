<?php

namespace App\Http\Controllers;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LearningPathController extends Controller
{
    public function index(Request $request): View
    {
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->withCount('lessons')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $hasCompletedInterview = LearnerProfile::completedFor($request->user()?->id, $request->cookie('artaswaha_device')) !== null;

        return view('welcome', compact('learningPaths', 'hasCompletedInterview'));
    }

    public function show(Request $request, LearningPath $learningPath): View
    {
        abort_unless($learningPath->is_published, 404);

        $learningPath->load('lessons');

        $profile = LearnerProfile::query()
            ->where('visitor_id', $request->cookie('artaswaha_device'))
            ->where('learning_path_id', $learningPath->id)
            ->where('user_id', $request->user()?->id)
            ->first();

        return view('learning.paths.show', compact('learningPath', 'profile'));
    }
}
