<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Gantikan default BladeCompiler dengan SafeBladeCompiler agar kebal terhadap error touch() utime di Linux VPS
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new \App\View\SafeBladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
                $app['config']->get('view.check_cache_timestamps', true),
            ), function ($blade) {
                $blade->component('dynamic-component', \Illuminate\View\DynamicComponent::class);
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa skema HTTPS untuk semua URL/route di environment SSL/Cloudflare Tunnel
        if (config('app.env') === 'production' || str_contains(config('app.url'), 'https://') || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }
    }
}
