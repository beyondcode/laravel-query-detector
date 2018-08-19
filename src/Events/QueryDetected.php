<?php

namespace BeyondCode\QueryDetector\Events;

use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;

class QueryDetected
{
    use SerializesModels;

    /**
     * The queries collection.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $queries;

    /**
     * QueryDetected constructor.
     *
     * @param  \Illuminate\Support\Collection  $queries
     * @return void
     */
    public function __construct(Collection $queries)
    {
        $this->queries = $queries;
    }

    /**
     * get the queries collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQueries()
    {
        return $this->queries;
    }
}
