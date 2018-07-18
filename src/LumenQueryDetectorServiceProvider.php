<?php
namespace BeyondCode\QueryDetector;

use Illuminate\Support\ServiceProvider;

class LumenQueryDetectorServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->configure('querydetector');
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'querydetector');

        $this->app->middleware([
            QueryDetectorMiddleware::class
        ]);

        $this->app->singleton(QueryDetector::class);
        $this->app->alias(QueryDetector::class, 'querydetector');
    }
}
