<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MeasureRequestTiming
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $previousDurations = [];
        foreach (DB::getConnections() as $name => $connection) {
            $previousDurations[$name] = $connection->totalQueryDuration();
        }

        $response = $next($request);
        $elapsed = (hrtime(true) - $startedAt) / 1_000_000;
        $databaseDuration = 0.0;
        foreach (DB::getConnections() as $name => $connection) {
            $databaseDuration += max(0, $connection->totalQueryDuration() - ($previousDurations[$name] ?? 0));
        }

        $timing = sprintf('app;dur=%.2F;desc="Laravel middleware", db;dur=%.2F;desc="SQL execution"', $elapsed, $databaseDuration);
        $existing = $response->headers->get('Server-Timing');
        $response->headers->set('Server-Timing', $existing ? $existing.', '.$timing : $timing);

        return $response;
    }
}
