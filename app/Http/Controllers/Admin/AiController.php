<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\QueueLearnerAnalyses;
use App\Models\AiReport;
use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\QuizDraft;
use App\Services\AdminAiService;
use App\Services\ContentAudioService;
use App\Services\LearnerInterviewAnalyzer;
use App\Services\PagePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Inertia\Response as InertiaResponse;
use Throwable;

class AiController extends Controller
{
    public function index(Request $request, AdminAiService $service, LearnerInterviewAnalyzer $analyzer, ContentAudioService $audioService): InertiaResponse
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:overview,interviews,drafts,services'], 'role' => ['nullable', 'in:murid,guru,pendatang,masyarakat'],
            'status' => ['nullable', 'in:queued,processing,completed,failed,unavailable,outdated,not_requested'],
            'mode' => ['nullable', 'in:account,anonymous'], 'learning_path_id' => ['nullable', 'integer'],
            'start' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start', 'before_or_equal:today'],
        ]);
        $start = Carbon::parse($filters['start'] ?? now()->subDays(13)->toDateString())->startOfDay();
        $end = Carbon::parse($filters['end'] ?? now()->toDateString())->endOfDay();
        abort_if($start->gt($end) || $start->diffInDays($end) > 93, 422, 'Pilih periode maksimal 93 hari.');
        $tab = $filters['tab'] ?? 'overview';
        $snapshot = $tab === 'overview' ? $service->snapshot($start, $end) : [];
        $statuses = $tab === 'overview' ? LearnerProfile::select('analysis_status')->selectRaw('COUNT(*) as total')->groupBy('analysis_status')->get() : collect();
        $profiles = $tab === 'interviews' ? LearnerProfile::with('learningPath:id,title')
            ->when($request->filled('start') || $request->filled('end'), fn (Builder $query): Builder => $query->whereBetween('analysis_requested_at', [$start, $end]))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->when($filters['mode'] ?? null, fn (Builder $query, string $mode): Builder => $query->where('mode', $mode))
            ->when($filters['learning_path_id'] ?? null, fn (Builder $query, mixed $path): Builder => $query->where('learning_path_id', $path))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $status === 'not_requested' ? $query->whereNull('analysis_status') : $query->where('analysis_status', $status))
            ->latest('id')->paginate(20)->withQueryString() : null;
        $drafts = $tab === 'drafts' ? QuizDraft::latest('id')->paginate(20)->withQueryString() : null;
        $reports = $tab === 'overview' ? AiReport::latest('id')->paginate(10)->withQueryString() : null;
        $learningPaths = $tab === 'interviews' ? LearningPath::orderBy('title')->get(['id', 'title']) : collect();
        $lastCheck = Cache::get('learner-analysis-last-check');
        $lastWorker = Cache::get('learner-analysis-last-worker');
        $aiConfigured = $analyzer->isConfigured();
        $ttsConfigured = $audioService->isConfigured();
        $pendingJobs = null;
        $queueUnavailable = false;
        if ($tab === 'services') {
            try {
                $pendingJobs = Queue::connection()->size();
            } catch (Throwable $exception) {
                $queueUnavailable = true;
                Log::warning('Admin could not read application queue.', ['exception_type' => $exception::class]);
            }
        }
        $audioCounts = $tab === 'services' ? ContentAudio::select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status') : collect();

        return app(PagePresenter::class)->render('admin.ai.index', compact('tab', 'start', 'end', 'snapshot', 'statuses', 'profiles', 'drafts', 'reports', 'learningPaths', 'aiConfigured', 'ttsConfigured', 'pendingJobs', 'queueUnavailable', 'audioCounts', 'lastCheck', 'lastWorker'));
    }

    public function profile(LearnerProfile $learnerProfile, LearnerInterviewAnalyzer $analyzer): InertiaResponse
    {
        $paths = LearningPath::whereKey($learnerProfile->learning_path_ids ?: [$learnerProfile->learning_path_id])->get();
        $analysis = $analyzer->presentation($learnerProfile, $paths);

        return app(PagePresenter::class)->render('admin.ai.profile', compact('learnerProfile', 'paths', 'analysis'));
    }

    public function retry(LearnerProfile $learnerProfile, LearnerInterviewAnalyzer $analyzer): RedirectResponse
    {
        $analyzer->enqueue($learnerProfile, force: true);

        return back()->with('status', 'Tes analisis diminta. Hasil sebelumnya tetap tampil; pekerjaan yang masih aktif tidak diduplikasi.');
    }

    public function testAll(LearnerInterviewAnalyzer $analyzer): RedirectResponse
    {
        if (! $analyzer->isConfigured()) {
            return back()->withErrors(['analysis' => 'AI_API_URL atau AI_MODEL belum dikonfigurasi.']);
        }
        QueueLearnerAnalyses::dispatch((int) LearnerProfile::query()->max('id'));

        return to_route('admin.ai.index', ['tab' => 'interviews'])->with('status', 'Tes semua profil langsung masuk antrean tanpa menunggu jadwal 6 jam. Worker memproses bergiliran; buka detail pelajar untuk melihat riwayat dan hasil.');
    }

    public function draft(QuizDraft $quizDraft): InertiaResponse
    {
        return app(PagePresenter::class)->render('admin.ai.draft', compact('quizDraft'));
    }

    public function requestReport(Request $request, AdminAiService $service): RedirectResponse
    {
        $data = $request->validate(['start' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start', 'before_or_equal:today']]);
        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);
        abort_if($start->diffInDays($end) >= 93, 422, 'Pilih periode maksimal 93 hari.');
        $report = $service->requestReport($start, $end);

        return to_route('admin.ai.reports.show', $report);
    }

    public function report(AiReport $aiReport): InertiaResponse
    {
        return app(PagePresenter::class)->render('admin.ai.report', compact('aiReport'));
    }
}
