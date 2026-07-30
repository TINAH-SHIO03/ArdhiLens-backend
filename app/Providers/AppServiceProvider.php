<?php

namespace App\Providers;

use App\Services\Identity\HttpNidaProvider;
use App\Services\Identity\LocalNidaProvider;
use App\Services\Identity\NidaProviderInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocalNidaProvider::class);
        $this->app->singleton(NidaProviderInterface::class, function ($app) {
            $driver = config('services.nida.driver', 'local');

            return $driver === 'http'
                ? $app->make(HttpNidaProvider::class)
                : $app->make(LocalNidaProvider::class);
        });
    }

    public function boot(): void
    {
        $appUrl = (string) config('app.url');

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(90)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
