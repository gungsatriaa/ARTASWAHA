<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PagePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Response as InertiaResponse;

class AnalyticsController extends Controller
{
    public function index(): InertiaResponse
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
            'anonymousProfiles' => DB::table('learner_profiles')->where('mode', 'anonymous')->count(),
            'completedLessons' => DB::table('learning_progress')->whereNotNull('completed_at')->count(),
            'learners' => DB::table('learning_progress')->whereNotNull('completed_at')->distinct()->count('visitor_id'),
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

        return app(PagePresenter::class)->render('admin.analytics', compact('chart', 'metrics', 'topPaths'));
    }

    public function online(): JsonResponse
    {
        $counts = DB::table('active_visitors')
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN kind = ? THEN 1 ELSE 0 END) as accounts', ['account'])
            ->first();

        return response()->json([
            'total' => (int) $counts->total,
            'accounts' => (int) $counts->accounts,
            'anonymous' => (int) $counts->total - (int) $counts->accounts,
        ]);
    }
}
