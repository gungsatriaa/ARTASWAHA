<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $kind = $request->user() ? 'account' : 'anonymous';
        $identity = $request->user() ? 'user:'.$request->user()->getAuthIdentifier() : 'session:'.$request->session()->getId();

        DB::table('active_visitors')->upsert([
            ['visitor_key' => hash('sha256', $identity), 'kind' => $kind, 'last_seen_at' => now()],
        ], ['visitor_key'], ['kind', 'last_seen_at']);

        if (random_int(1, 100) === 1) {
            DB::table('active_visitors')->where('last_seen_at', '<', now()->subDay())->delete();
        }

        return response()->json(['ok' => true]);
    }
}
