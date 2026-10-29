<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReactPage implements Responsable
{
    /**
     * @param  array<string, mixed>  $props
     * @param  list<string>  $deferred
     */
    public function __construct(public string $component, public array $props = [], public array $deferred = []) {}

    /**
     * @param  array<string, mixed>  $props
     * @param  list<string>  $deferred
     */
    public static function render(string $component, array $props = [], array $deferred = []): self
    {
        return new self($component, $props, $deferred);
    }

    /** @param Request $request */
    public function toResponse($request): Response
    {
        $json = $request->header('X-React-Page') === 'true';
        $partial = $json && $request->hasHeader('X-React-Only') &&
            (! $request->hasHeader('X-React-Component') || $request->header('X-React-Component') === $this->component);
        $only = $partial ? array_filter(array_map('trim', explode(',', $request->header('X-React-Only')))) : [];
        $props = [...$request->attributes->get('react.shared', []), ...$this->props];
        $resolved = [];
        foreach ($props as $key => $value) {
            if ($partial && ! in_array($key, [...$only, 'viewer', 'csrfToken', 'errors', 'flash'], true)) {
                continue;
            }
            if (in_array($key, $this->deferred, true) && (! $partial || ! in_array($key, $only, true))) {
                continue;
            }
            $resolved[$key] = $value instanceof Closure ? $value() : $value;
        }
        $page = ['component' => $this->component, 'props' => $resolved, 'url' => $request->getRequestUri()];
        if (! $partial && $this->deferred !== []) {
            $page['deferred'] = $this->deferred;
        }
        // Normalize collections and paginators identically for HTML, JSON, and SSR.
        $page = json_decode(json_encode($page, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $headers = ['Cache-Control' => 'no-store, private', 'Vary' => 'X-React-Page, X-React-Only, X-React-Component'];
        if ($json) {
            return response()->json($page, headers: [...$headers, 'X-React-Page' => 'true']);
        }

        return response()->view('react', ['page' => $page, 'ssr' => $this->ssr($page)], headers: $headers);
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array{head: list<string>, body: string}|null
     */
    private function ssr(array $page): ?array
    {
        if (! config('react.ssr.enabled')) {
            return null;
        }
        try {
            $url = config('react.ssr.url');
            if (! in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost', '[::1]'], true)) {
                throw new RuntimeException('The React SSR endpoint must be loopback.');
            }
            $result = Http::connectTimeout(config('react.ssr.timeout'))->timeout(config('react.ssr.timeout'))
                ->withoutRedirecting()->post($url, $page)->throw()->json();
            if (! is_array($result) || ! is_array($result['head'] ?? null) || ! array_is_list($result['head']) ||
                ! is_string($result['body'] ?? null) || count(array_filter($result['head'], 'is_string')) !== count($result['head'])) {
                throw new RuntimeException('Invalid React SSR response.');
            }

            return ['head' => $result['head'], 'body' => $result['body']];
        } catch (Throwable $exception) {
            Log::warning('React SSR failed; using client rendering.', ['component' => $this->component, 'error' => $exception::class]);

            return null;
        }
    }
}
