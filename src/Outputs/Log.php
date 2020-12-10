<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Log implements Output
{
    public function boot()
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response)
    {
        $this->log('Detected N+1 Query');

        foreach ($detectedQueries as $detectedQuery) {
            $logOutput = 'Model: '.$detectedQuery['model'] . PHP_EOL;

            $logOutput .= 'Relation: '.$detectedQuery['relation'] . PHP_EOL;

            $logOutput .= 'Num-Called: '.$detectedQuery['count'] . PHP_EOL;

            $logOutput .= 'Call-Stack:' . PHP_EOL;

            foreach ($detectedQuery['sources'] as $source) {
                $logOutput .= '#'.$source->index.' '.$source->name.':'.$source->line . PHP_EOL;
            }

            $this->log($logOutput);
        }
    }

    private function log(string $message)
    {
        LaravelLog::channel(config('querydetector.log_channel'))->info($message);
    }
}
