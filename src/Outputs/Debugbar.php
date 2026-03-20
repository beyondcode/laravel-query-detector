<?php

namespace BeyondCode\QueryDetector\Outputs;

use DebugBar\DataCollector\MessagesCollector;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Debugbar implements Output
{
    protected $collector;

    public function boot()
    {
        $this->collector = new MessagesCollector('N+1 Queries');

        $facade = $this->resolveDebugbarFacade();

        if ($facade === null) {
            return;
        }

        if (! $facade::hasCollector($this->collector->getName())) {
            $facade::addCollector($this->collector);
        }
    }

    public function output(Collection $detectedQueries, Response $response)
    {
        foreach ($detectedQueries as $detectedQuery) {
            $this->collector->addMessage(sprintf(
                'Model: %s => Relation: %s - You should add `with(%s)` to eager-load this relation.',
                $detectedQuery['model'],
                $detectedQuery['relation'],
                $detectedQuery['relation']
            ));
        }
    }

    /**
     * Resolve the installed Debugbar facade at runtime.
     *
     * Supports barryvdh/laravel-debugbar (v3 and v4+) and
     * fruitcake/laravel-debugbar. Returns the first class
     * that the autoloader can locate, or null if none exist.
     *
     * @return string|null
     */
    protected function resolveDebugbarFacade()
    {
        $candidates = [
            'Barryvdh\Debugbar\Facades\Debugbar',
            'Barryvdh\Debugbar\Facade',
            'Fruitcake\LaravelDebugbar\Facades\Debugbar',
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
