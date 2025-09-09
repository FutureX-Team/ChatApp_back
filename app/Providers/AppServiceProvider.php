<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('per-user-10pm', function (Request $request) {
            // لو فيه مستخدم موثّق استخدم id، غير كذا استخدم IP
            return [
                Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }
}
