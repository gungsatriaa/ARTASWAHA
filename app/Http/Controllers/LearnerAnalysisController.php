<?php

namespace App\Http\Controllers;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearnerAnalysisController extends Controller
{
    public function show(Request $request, LearnerInterviewAnalyzer $analyzer): JsonResponse
    {
        $profile = $this->profile($request);
        $paths = LearningPath::query()->whereKey($profile->learning_path_ids ?: [$profile->learning_path_id])->where('is_published', true)->get();
        $analysis = $analyzer->presentation($profile, $paths);

        return response()->json([
            'analysis' => $analysis,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, LearnerInterviewAnalyzer $analyzer): RedirectResponse
    {
        $analyzer->enqueue($this->profile($request));

        return to_route('profile.show')->withFragment('analisis')->with('status', 'Status analisis diperbarui. Kamu tetap bisa melanjutkan belajar.');
    }

    private function profile(Request $request): LearnerProfile
    {
        $profile = LearnerProfile::completedFor($request->user()?->id, $request->cookie('artaswaha_device'));
        abort_if($profile === null, 404);

        return $profile;
    }
}
