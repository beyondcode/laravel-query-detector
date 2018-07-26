<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

use Debugbar as LaravelDebugbar;
use DebugBar\DataCollector\MessagesCollector;

class Debugbar implements Output
{
    protected $collector;

    public function boot()
    {
        $this->collector = new MessagesCollector('N+1 Queries');

        LaravelDebugbar::addCollector($this->collector);
    }

    public function output(Collection $detectedQueries, Response $response)
    {
        foreach ($detectedQueries as $detectedQuery) {
            $this->collector->addMessage(sprintf('Model: %s => Relation: %s - You should add `with(%s)` to eager-load this relation.',
                $detectedQuery['model'],
                $detectedQuery['relation'],
                $detectedQuery['relation']
            ));
        }
    }
}
