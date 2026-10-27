<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\AdminAnalyticsService;
use App\Services\PagePresenter;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function __invoke(AdminAnalyticsService $analytics): InertiaResponse
    {
        $lessonCounts = Lesson::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_published = 1 and reviewed_at is not null then 1 else 0 end) as published')
            ->selectRaw('sum(case when reviewed_at is null then 1 else 0 end) as drafts')
            ->selectRaw('sum(case when is_published = 0 and reviewed_at is not null then 1 else 0 end) as archived')
            ->first();

        $learningPaths = LearningPath::query()
            ->withCount('allLessons')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $recentLessons = Lesson::query()
            ->with('learningPath:id,title')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $overview = $analytics->snapshot();
        $aiTasks = [
            'failed_interviews' => $overview['metrics']['failedAnalysis'],
            'audio_review' => $overview['metrics']['audioReview'],
            'audio_failed' => $overview['metrics']['audioFailed'],
        ];

        return app(PagePresenter::class)->render('admin.dashboard', compact('lessonCounts', 'learningPaths', 'recentLessons', 'aiTasks', 'overview'));
    }
}
