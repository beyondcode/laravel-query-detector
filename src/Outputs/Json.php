<?php
namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class Json implements Output
{
    public function boot()
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response)
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            if (! is_array($data)){
                $data = [ $data ];
            }
            
            $data['warning_queries'] = $detectedQueries;
            $response->setData($data);
        }
    }
}
