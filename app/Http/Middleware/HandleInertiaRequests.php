<?php

namespace App\Http\Middleware;

use App\Models\LearningPath;
use App\Services\ReactUi;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'react';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'materials' => fn (): array => collect(['paribasa', 'aksara'])->mapWithKeys(fn (string $group): array => [$group => LearningPath::query()->where('is_published', true)->where('group', $group)->orderBy('sort_order')->orderBy('id')->limit(12)->get(['id', 'title', 'slug'])->map(fn (LearningPath $path): array => ['id' => $path->id, 'title' => $path->title, 'url' => route('learning.paths.show', $path->slug)])->all()])->all(),
            'viewer' => fn (): ?array => app(ReactUi::class)->viewer($request),
            'flash' => fn (): array => [
                'message' => $request->session()->get('status'),
                'tone' => $request->session()->get('status_tone', 'success'),
            ],
            'navigation' => fn (): array => [
                'home' => route('home'), 'learning' => route('learning.paths.index'),
                'quizzes' => route('quizzes.index'), 'profile' => route('profile.show'),
                'login' => route('login'), 'register' => route('register'),
                'logout' => route('logout'), 'admin' => route('admin.index'),
                'privacy' => route('privacy-policy'), 'presence' => route('presence.ping'),
            ],
            'brand' => fn (): array => ['avatarBase' => asset('avatar'), 'logo' => asset('image/maskot.webp'), 'mascot' => asset('image/asset1.webp')],
            'csrfToken' => fn (): string => csrf_token(),
            'routeName' => fn (): ?string => $request->route()?->getName(),
            'links' => fn (): array => collect([
                'learning.interview.show', 'learning.interview.store', 'login.store', 'register.store', 'password.request', 'password.email', 'password.update', 'auth.google.redirect',
                'profile.about', 'profile.destroy', 'profile.analysis.show', 'profile.avatar.update', 'quizzes.create', 'quizzes.store', 'quizzes.drafts.store', 'rooms.join',
                'admin.index', 'admin.analytics.index', 'admin.analytics.online', 'admin.learning-paths.index', 'admin.learning-paths.create', 'admin.learning-paths.store',
                'admin.lessons.index', 'admin.lessons.create', 'admin.lessons.store', 'admin.audio.index', 'admin.ai.index', 'admin.ai.reports.store',
            ])->mapWithKeys(fn (string $name): array => [$name => route($name)])->all(),
        ];
    }
}
