<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimiterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // General API: 120 requests/minute per user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Too many requests. Please slow down.',
                ], 429));
        });

        // Payroll endpoint: 10 requests/minute (expensive operation)
        RateLimiter::for('payroll', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Payroll rate limit exceeded.',
                ], 429));
        });

        // Auth endpoints: 5 attempts/minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Too many login attempts. Try again later.',
                ], 429));
        });
    }
}
