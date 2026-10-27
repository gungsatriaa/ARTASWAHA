<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAnalyticsService;
use App\Services\PagePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Response as InertiaResponse;

class AnalyticsController extends Controller
{
    public function index(AdminAnalyticsService $analytics): InertiaResponse
    {
        return app(PagePresenter::class)->render('admin.analytics', $analytics->snapshot());
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
