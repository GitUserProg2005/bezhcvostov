<?php

namespace App\Http\Controllers;

use App\Events\CounterUpdated;
use App\Services\Prometheus\Metrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CounterController extends Controller
{
    public function index()
    {
        try {
            $count = (int) Cache::get('ws_counter', 0);
        } catch (\Throwable $e) {
            report($e);
            $count = 0;
        }

        return Inertia::render('Counter', [
            'count' => $count,
        ]);
    }

    public function increment(Request $request, Metrics $metrics)
    {
        $count = 0;
        try {
            $count = (int) Cache::get('ws_counter', 0);
            $count++;
            Cache::put('ws_counter', $count);
        } catch (\Throwable $e) {
            report($e);
            $count = max(1, $count);
        }

        try {
            Log::info('[counter.increment] dispatch CounterUpdated', [
                'count' => $count,
            ]);
            event(new CounterUpdated($count));
        } catch (\Throwable $e) {
            Log::error('[counter.increment] dispatch failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);
        }

        $metrics->counter('ws_counter_increment_total', 'Total number of WebSocket counter increments')->inc();

        return response()->json(['count' => $count]);
    }
}
