<?php

namespace BeyondCode\QueryDetector;

use Closure;

class QueryDetectorMiddleware
{
    /**
     * The query detector.
     *
     * @var \BeyondCode\QueryDetector\QueryDetector
     */
    private $detector;

    /**
     * QueryDetectorMiddleware constructor.
     *
     * @param  \BeyondCode\QueryDetector\QueryDetector  $detector
     * @return void
     */
    public function __construct(QueryDetector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->detector->isEnabled()) {
            return $next($request);
        }

        $this->detector->boot();

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        // Modify the response to add the Debugbar
        $this->detector->output($request, $response);

        return $response;
    }
}
