<?php

namespace App\Providers;

use App\Models\LearningPath;
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
