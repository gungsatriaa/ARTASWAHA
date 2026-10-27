<?php

namespace App\Providers;

use App\Jobs\AnalyzeLearnerInterview;
use App\Models\LearningPath;
use App\Services\ChatterboxProcess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ChatterboxProcess::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::before(function (JobProcessing $event): void {
            if ($event->job->resolveName() !== AnalyzeLearnerInterview::class) {
                return;
            }

            Log::info('Learner analysis job received by worker.', ['job_id' => $event->job->getJobId(), 'connection' => $event->connectionName, 'queue' => $event->job->getQueue(), 'attempt' => $event->job->attempts()]);
        });
        Queue::after(function (JobProcessed $event): void {
            if ($event->job->resolveName() !== AnalyzeLearnerInterview::class || ! $event->job->isReleased()) {
                return;
            }

            Log::warning('Learner analysis job released back to queue.', ['job_id' => $event->job->getJobId(), 'connection' => $event->connectionName, 'queue' => $event->job->getQueue(), 'attempt' => $event->job->attempts()]);
        });

        RateLimiter::for('learner-analysis', fn (): Limit => Limit::perMinute(10)->by('learner-llm'));
        RateLimiter::for('admin-ai-write', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->id));
        RateLimiter::for('learner-analysis-read', fn (Request $request): Limit => Limit::perMinute(30)
            ->by($request->user()?->id ?? $request->cookie('artaswaha_device') ?? $request->ip()));
        RateLimiter::for('learner-analysis-write', fn (Request $request): Limit => Limit::perMinutes(10, 3)
            ->by($request->user()?->id ?? $request->cookie('artaswaha_device') ?? $request->ip()));

        ViewFacade::composer('components.layouts.app', function (View $view): void {
            if ($view->getData()['learningPaths'] ?? null) {
                return;
            }

            $view->with('learningPaths', LearningPath::query()
                ->where('is_published', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get());
        });
    }
}
