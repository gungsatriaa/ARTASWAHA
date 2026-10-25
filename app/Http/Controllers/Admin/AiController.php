<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiReport;
use App\Models\ContentAudio;
use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\QuizDraft;
use App\Services\AdminAiService;
use App\Services\ContentAudioService;
use App\Services\LearnerInterviewAnalyzer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiController extends Controller
{
    public function index(Request $request, AdminAiService $service, LearnerInterviewAnalyzer $analyzer, ContentAudioService $audioService): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:overview,interviews,drafts,services'], 'role' => ['nullable', 'in:murid,guru,pendatang,masyarakat'],
            'status' => ['nullable', 'in:queued,processing,completed,failed,unavailable,not_requested'],
            'mode' => ['nullable', 'in:account,anonymous'], 'learning_path_id' => ['nullable', 'integer'],
            'start' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start', 'before_or_equal:today'],
        ]);
        $start = Carbon::parse($filters['start'] ?? now()->subDays(13)->toDateString())->startOfDay();
        $end = Carbon::parse($filters['end'] ?? now()->toDateString())->endOfDay();
        abort_if($start->gt($end) || $start->diffInDays($end) > 93, 422, 'Pilih periode maksimal 93 hari.');
        $tab = $filters['tab'] ?? 'overview';
        $snapshot = $tab === 'overview' ? $service->snapshot($start, $end) : [];
        $statuses = $tab === 'overview' ? LearnerProfile::select('analysis_status')->selectRaw('COUNT(*) as total')->groupBy('analysis_status')->get() : collect();
        $profiles = $tab === 'interviews' ? LearnerProfile::with('learningPath:id,title')->whereBetween('created_at', [$start, $end])
            ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->when($filters['mode'] ?? null, fn (Builder $query, string $mode): Builder => $query->where('mode', $mode))
            ->when($filters['learning_path_id'] ?? null, fn (Builder $query, mixed $path): Builder => $query->where('learning_path_id', $path))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $status === 'not_requested' ? $query->whereNull('analysis_status') : $query->where('analysis_status', $status))
            ->latest('id')->paginate(20)->withQueryString() : null;
        $drafts = $tab === 'drafts' ? QuizDraft::latest('id')->paginate(20)->withQueryString() : null;
        $reports = $tab === 'overview' ? AiReport::latest('id')->paginate(10)->withQueryString() : null;
        $learningPaths = $tab === 'interviews' ? LearningPath::orderBy('title')->get(['id', 'title']) : collect();
        $aiConfigured = $analyzer->isConfigured();
        $ttsConfigured = $audioService->isConfigured();
        $pendingJobs = $tab === 'services' ? DB::table('jobs')->where('queue', 'learner-analysis')->count() : 0;
        $audioCounts = $tab === 'services' ? ContentAudio::select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status') : collect();

        return view('admin.ai.index', compact('tab', 'start', 'end', 'snapshot', 'statuses', 'profiles', 'drafts', 'reports', 'learningPaths', 'aiConfigured', 'ttsConfigured', 'pendingJobs', 'audioCounts'));
    }

    public function profile(LearnerProfile $learnerProfile, LearnerInterviewAnalyzer $analyzer): View
    {
        $paths = LearningPath::whereKey($learnerProfile->learning_path_ids ?: [$learnerProfile->learning_path_id])->get();
        $analysis = $analyzer->presentation($learnerProfile, $paths);

        return view('admin.ai.profile', compact('learnerProfile', 'paths', 'analysis'));
    }

    public function retry(LearnerProfile $learnerProfile, LearnerInterviewAnalyzer $analyzer): RedirectResponse
    {
        $analyzer->enqueue($learnerProfile);

        return back()->with('status', 'Permintaan diperiksa. Hasil yang masih berlaku digunakan kembali; permintaan ganda tidak diproses ulang.');
    }

    public function draft(QuizDraft $quizDraft): View
    {
        return view('admin.ai.draft', compact('quizDraft'));
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

    public function report(AiReport $aiReport): View
    {
        return view('admin.ai.report', compact('aiReport'));
    }
}
