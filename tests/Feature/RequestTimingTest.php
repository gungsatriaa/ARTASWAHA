<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequestTimingTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_includes_timings_without_query_details_or_query_logging(): void
    {
        Route::get('/timing-test', function () {
            DB::select('select 1 as timing_secret');

            return response()->json(['ok' => true])->header('Server-Timing', 'custom;dur=1');
        });
        DB::disableQueryLog();
        $response = $this->getJson('/timing-test')->assertOk()->assertExactJson(['ok' => true]);
        $header = $response->headers->get('Server-Timing');

        $this->assertMatchesRegularExpression('/custom;dur=1, app;dur=\d+\.\d{2};desc="Laravel middleware", db;dur=\d+\.\d{2};desc="SQL execution"/', $header);
        $this->assertStringNotContainsString('timing_secret', $header);
        $this->assertFalse(DB::connection()->logging());
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_timings_do_not_accumulate_between_requests(): void
    {
        Route::get('/timing-test', fn () => response('ready'));
        DB::connection()->logQuery('earlier-request', [], 5000);
        $response = $this->get('/timing-test')->assertOk();
        preg_match('/db;dur=([\d.]+)/', $response->headers->get('Server-Timing'), $matches);

        $this->assertLessThan(5000, (float) $matches[1]);
    }
}
