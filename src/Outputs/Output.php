<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

interface Output
{
    /**
     * Boot the output.
     *
     * @return mixed
     */
    public function boot();

    /**
     * Generate the output.
     *
     * @param  \Illuminate\Support\Collection  $detectedQueries
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function output(Collection $detectedQueries, Response $response);
}
