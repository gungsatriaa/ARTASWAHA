<?php

namespace App\Providers;

use App\Models\LearningPath;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
