<?php

namespace App\Http\Middleware;

use App\Services\ReactUi;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

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
            'brand' => fn (): array => ['logo' => asset('image/maskot.webp'), 'mascot' => asset('image/asset1.webp')],
            'csrfToken' => fn (): string => csrf_token(),
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);
        if ($request->header('X-Inertia') && $request->isMethod('GET') && $response->isSuccessful()
            && ! $response->headers->has('X-Inertia') && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            return Inertia::location($request->fullUrl());
        }

        return $response;
    }
}
