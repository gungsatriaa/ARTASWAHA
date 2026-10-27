<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $start = now()->startOfDay()->subDays(13);
        $days = collect(range(0, 13))->map(fn (int $offset): string => $start->copy()->addDays($offset)->toDateString());

        $daily = [
            'users' => DB::table('users')->where('created_at', '>=', $start)->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day'),
            'completions' => DB::table('learning_progress')->whereNotNull('completed_at')->where('completed_at', '>=', $start)->selectRaw('DATE(completed_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day'),
            'answers' => DB::table('room_answers')->where('answered_at', '>=', $start)->selectRaw('DATE(answered_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day'),
        ];

        $chart = [
            'labels' => $days->map(fn (string $day): string => date('d M', strtotime($day)))->all(),
            'users' => $days->map(fn (string $day): int => (int) ($daily['users'][$day] ?? 0))->all(),
            'completions' => $days->map(fn (string $day): int => (int) ($daily['completions'][$day] ?? 0))->all(),
            'answers' => $days->map(fn (string $day): int => (int) ($daily['answers'][$day] ?? 0))->all(),
        ];

        $answerCounts = DB::table('room_answers')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->first();

        $metrics = [
            'users' => DB::table('users')->count(),
            'anonymousProfiles' => DB::table('learner_profiles')->where('mode', 'anonymous')->whereNull('user_id')->count(),
            'completedLessons' => DB::table('learning_progress')->whereNotNull('completed_at')->count(),
            'learners' => (int) DB::table('learning_progress')->whereNotNull('completed_at')->selectRaw('COUNT(DISTINCT user_id) + COUNT(DISTINCT CASE WHEN user_id IS NULL THEN visitor_id END) as total')->value('total'),
            'quizzes' => DB::table('quizzes')->count(),
            'rooms' => DB::table('rooms')->count(),
            'answers' => (int) $answerCounts->total,
            'accuracy' => $answerCounts->total ? round(100 * $answerCounts->correct / $answerCounts->total, 1) : null,
        ];

        $topPaths = DB::table('learning_progress')
            ->join('lessons', 'learning_progress.lesson_id', '=', 'lessons.id')
            ->join('learning_paths', 'lessons.learning_path_id', '=', 'learning_paths.id')
            ->whereNotNull('learning_progress.completed_at')
            ->select('learning_paths.title')
            ->selectRaw('COUNT(*) as completions')
            ->groupBy('learning_paths.id', 'learning_paths.title')
            ->orderByDesc('completions')
            ->limit(5)
            ->get();

        $weekly = [
            'completions' => ['current' => array_sum(array_slice($chart['completions'], 7)), 'previous' => array_sum(array_slice($chart['completions'], 0, 7))],
            'answers' => ['current' => array_sum(array_slice($chart['answers'], 7)), 'previous' => array_sum(array_slice($chart['answers'], 0, 7))],
            'users' => ['current' => array_sum(array_slice($chart['users'], 7)), 'previous' => array_sum(array_slice($chart['users'], 0, 7))],
        ];
        $roomStatuses = DB::table('rooms')->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($count): int => (int) $count)->all();
        $analysisStatuses = DB::table('learner_profiles')->whereNotNull('analysis_status')->select('analysis_status')->selectRaw('COUNT(*) as total')->groupBy('analysis_status')->pluck('total', 'analysis_status')->map(fn ($count): int => (int) $count)->all();
        $roles = DB::table('learner_profiles')->select('role')->selectRaw('COUNT(*) as total')->groupBy('role')->orderByDesc('total')->get();
        $dailyAccuracy = DB::table('room_answers')->where('answered_at', '>=', $start)
            ->selectRaw('DATE(answered_at) as day, COUNT(*) as total, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')->groupBy('day')->get()->keyBy('day');
        $chart['accuracy'] = $days->map(fn (string $day): ?float => isset($dailyAccuracy[$day]) ? round(100 * $dailyAccuracy[$day]->correct / $dailyAccuracy[$day]->total, 1) : null)->all();
        $metrics['pendingAnalysis'] = ($analysisStatuses['queued'] ?? 0) + ($analysisStatuses['processing'] ?? 0);
        $metrics['delayedAnalysis'] = DB::table('learner_profiles')->whereIn('analysis_status', ['queued', 'processing'])->where('analysis_requested_at', '<=', now()->subMinutes(10))->count();
        $metrics['failedAnalysis'] = $analysisStatuses['failed'] ?? 0;
        $metrics['audioReview'] = DB::table('content_audio')->where('status', 'review')->count();
        $metrics['audioFailed'] = DB::table('content_audio')->whereIn('status', ['failed', 'stale'])->count();

        return compact('chart', 'metrics', 'topPaths', 'weekly', 'roomStatuses', 'analysisStatuses', 'roles');
    }
}
