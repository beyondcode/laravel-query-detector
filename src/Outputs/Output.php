<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

interface Output
{
    public function output(Collection $detectedQueries, Response $response);
}