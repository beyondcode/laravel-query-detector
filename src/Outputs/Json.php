<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Json implements Output
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
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $data['warning_queries'] = $detectedQueries;
            $response->setData($data);
        }
    }
}
