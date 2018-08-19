<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Clockwork implements Output
{
    /**
     * Boot the Output.
     *
     * @return void
     */
    public function boot()
    {
        //
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
        clock()->warning("{$detectedQueries->count()} N+1 queries detected:", $detectedQueries->toArray());
    }
}
