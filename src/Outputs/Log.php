<?php

namespace BeyondCode\QueryDetector\Outputs;

use Log as LaravelLog;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Log implements Output
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
        LaravelLog::info('Detected N+1 Query');

        foreach ($detectedQueries as $detectedQuery) {
            LaravelLog::info('Model: '.$detectedQuery['model']);
            LaravelLog::info('Relation: '.$detectedQuery['relation']);
            LaravelLog::info('Num-Called: '.$detectedQuery['count']);

            LaravelLog::info('Call-Stack:');

            foreach ($detectedQuery['sources'] as $source) {
                LaravelLog::info('#'.$source->index.' '.$source->name.':'.$source->line);
            }
        }
    }
}
