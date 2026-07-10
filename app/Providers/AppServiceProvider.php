<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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


    public function boot()
    {
        // Rate limiter for auth routes: 5 attempts per minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        if (app()->environment('production')) {
            DB::listen(function ($query) {
                if ($query->time > 500) { // ms
                    Log::warning('Slow Query', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }
    }

}
