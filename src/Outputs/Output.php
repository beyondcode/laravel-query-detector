<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

interface Output
{
    public function boot();

    public function output(Collection $detectedQueries, Response $response);
}
