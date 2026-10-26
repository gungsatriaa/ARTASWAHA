<?php

namespace App\Http\Controllers;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\LearningProgress;
use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LearningPathController extends Controller
{
    public function catalog(): View
    {
        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->withCount('lessons')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return view('learning.paths.index', compact('learningPaths'));
    }

    public function index(Request $request, LearnerInterviewAnalyzer $analyzer): View
    {
        $user = $request->user();
        $profile = LearnerProfile::completedFor($user?->id, $request->cookie('artaswaha_device'));
        $showMobileDashboard = $user !== null || $profile !== null;

        $learningPathsQuery = LearningPath::query()
            ->where('is_published', true)
            ->withCount('lessons')
            ->orderBy('group')
            ->orderBy('sort_order');

        if ($showMobileDashboard) {
            $learningPathsQuery->withCount([
                'lessons as completed_lessons_count' => fn (Builder $query): Builder => $query->whereHas(
                    'progress',
                    fn (Builder $progressQuery): Builder => $progressQuery
                        ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
                        ->when($user === null, fn (Builder $query): Builder => $query->where('visitor_id', $profile->visitor_id)->whereNull('user_id'))
                        ->whereNotNull('completed_at'),
                ),
            ]);
        }

        $learningPaths = $learningPathsQuery->get();
        $selectedIds = $profile?->learning_path_ids ?: array_filter([$profile?->learning_path_id]);
        $dashboardPaths = $profile !== null ? $learningPaths->whereIn('id', $selectedIds)->values() : $learningPaths;
        $featuredPath = $dashboardPaths->first(fn (LearningPath $path): bool => $path->lessons_count > 0 && $path->completed_lessons_count < $path->lessons_count)
            ?? $dashboardPaths->first();

        $lastLearningProgress = $showMobileDashboard ? LearningProgress::query()
            ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
            ->when($user === null, fn (Builder $query): Builder => $query->where('visitor_id', $profile->visitor_id)->whereNull('user_id'))
            ->whereNotNull('completed_at')
            ->whereHas('lesson', fn (Builder $query): Builder => $query
                ->where('is_published', true)
                ->whereNotNull('reviewed_at')
                ->whereHas('learningPath', fn (Builder $query): Builder => $query->where('is_published', true)))
            ->with('lesson.learningPath')
            ->latest('completed_at')
            ->latest('id')
            ->first() : null;

        $hasCompletedInterview = $profile !== null;
        if ($profile !== null && $analyzer->isConfigured()) {
            $analyzer->enqueue($profile);
            $profile->refresh();
        }
        $analysis = $analyzer->presentation($profile, $learningPaths);

        return view('welcome', compact('learningPaths', 'hasCompletedInterview', 'showMobileDashboard', 'dashboardPaths', 'featuredPath', 'lastLearningProgress', 'profile', 'user', 'analysis'));
    }

    public function show(Request $request, LearningPath $learningPath, LearnerInterviewAnalyzer $analyzer): View
    {
        abort_unless($learningPath->is_published, 404);

        $learningPath->load('lessons');

        $profile = LearnerProfile::query()
            ->where('visitor_id', $request->cookie('artaswaha_device'))
            ->where('learning_path_id', $learningPath->id)
            ->where('user_id', $request->user()?->id)
            ->first();

        $analysisProfile = LearnerProfile::completedFor($request->user()?->id, $request->cookie('artaswaha_device'));
        $analysis = $analyzer->presentation($analysisProfile, collect([$learningPath]));

        return view('learning.paths.show', compact('learningPath', 'profile', 'analysis'));
    }
}
