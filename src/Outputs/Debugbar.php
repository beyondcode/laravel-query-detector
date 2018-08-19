<?php

namespace BeyondCode\QueryDetector\Outputs;

use Debugbar as LaravelDebugbar;
use Illuminate\Support\Collection;
use DebugBar\DataCollector\MessagesCollector;
use Symfony\Component\HttpFoundation\Response;

class Debugbar implements Output
{
    /**
     * The message collector.
     *
     * @var \DebugBar\DataCollector\MessagesCollector
     */
    protected $collector;

    /**
     * Boot the Output.
     *
     * @return void
     */
    public function boot()
    {
        $this->collector = new MessagesCollector('N+1 Queries');

        LaravelDebugbar::addCollector($this->collector);
    }

    /**
     * Generate the output.
     *
     * @param  \Illuminate\Support\Collection  $detectedQueries
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
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
