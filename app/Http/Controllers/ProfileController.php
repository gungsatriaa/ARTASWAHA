<?php

namespace App\Http\Controllers;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\PagePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class ProfileController extends Controller
{
    public function about(Request $request): InertiaResponse|RedirectResponse
    {
        $user = $request->user();
        $profile = $user === null ? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device')) : null;
        if ($user === null && $profile === null) {
            return to_route('login');
        }

        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return app(PagePresenter::class)->render('profile.about', compact('learningPaths', 'profile', 'user'));
    }

    public function __invoke(Request $request, LearnerInterviewAnalyzer $analyzer): InertiaResponse|RedirectResponse
    {
        $user = $request->user();
        $profile = $user === null ? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device')) : null;
        if ($user === null && $profile === null) {
            return to_route('login');
        }
        $profile?->load('learningPath');

        $learningPaths = LearningPath::query()
            ->where('is_published', true)
            ->withCount([
                'lessons',
                'lessons as completed_lessons_count' => fn (Builder $query): Builder => $query->whereHas(
                    'progress',
                    fn (Builder $progressQuery): Builder => $progressQuery
                        ->when($user !== null, fn (Builder $query): Builder => $query->where('user_id', $user->id))
                        ->when($profile !== null, fn (Builder $query): Builder => $query->where('visitor_id', $profile->visitor_id)->whereNull('user_id'))
                        ->whereNotNull('completed_at'),
                ),
            ])
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $studiedPaths = $learningPaths->where('completed_lessons_count', '>', 0);
        $learningPathsByGroup = $studiedPaths->groupBy('group');
        $totalLessons = $studiedPaths->sum('lessons_count');
        $completedLessons = $studiedPaths->sum('completed_lessons_count');

        $analysisProfile = $user === null ? $profile : LearnerProfile::completedFor($user->id, null);
        if ($analysisProfile !== null) {
            $analyzer->enqueue($analysisProfile);
            $analysisProfile->refresh();
        }
        $analysis = $analyzer->presentation($analysisProfile, $learningPaths);
        $activity = $analysisProfile !== null ? $analyzer->activity($analysisProfile) : null;

        return app(PagePresenter::class)->render('profile.show', compact('completedLessons', 'learningPaths', 'learningPathsByGroup', 'profile', 'totalLessons', 'user', 'analysisProfile', 'analysis', 'activity'));
    }
}
