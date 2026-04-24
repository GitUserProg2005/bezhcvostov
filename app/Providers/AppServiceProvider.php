<?php

namespace App\Providers;

use App\Services\Prometheus\Metrics;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Metrics::class, function () {
            return new Metrics;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Broadcast::routes(['middleware' => ['web', 'auth']]);
    }
}
