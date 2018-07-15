<?php

namespace BeyondCode\QueryDetector;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class QueryDetectorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('querydetector.php'),
            ], 'config');
        }

        $this->registerMiddleware(QueryDetectorMiddleware::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(QueryDetector::class);

        $this->app->alias(QueryDetector::class, 'querydetector');

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'querydetector');
    }

    /**
     * Register the middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app[Kernel::class];
        $kernel->pushMiddleware($middleware);
    }
}
