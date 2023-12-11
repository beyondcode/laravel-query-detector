<?php

namespace BeyondCode\QueryDetector;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\Relations\Relation;
use BeyondCode\QueryDetector\Events\QueryDetected;

class QueryDetector
{
    /** @var Collection */
    private $queries;
    /**
     * @var bool
     */
    private $booted = false;

    private function resetQueries()
    {
        $this->queries = Collection::make();
    }

    public function __construct()
    {
        $this->resetQueries();
    }

    public function boot()
    {
        if ($this->booted) {
            $this->resetQueries();
            return;
        }

        DB::listen(function ($query) {
            $backtrace = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50));

            $this->logQuery($query, $backtrace);
        });

        foreach ($this->getOutputTypes() as $outputType) {
            app()->singleton($outputType);
            app($outputType)->boot();
        }

        $this->booted = true;
    }

    public function isEnabled(): bool
    {
        $configEnabled = value(config('querydetector.enabled'));

        if ($configEnabled === null) {
            $configEnabled = config('app.debug');
        }

        return $configEnabled;
    }

    public function logQuery($query, Collection $backtrace)
    {
        $modelTrace = $backtrace->first(function ($trace) {
            return Arr::get($trace, 'object') instanceof Builder;
        });

        // The query is coming from an Eloquent model
        if (!is_null($modelTrace)) {
            /*
             * Relations get resolved by either calling the "getRelationValue" method on the model,
             * or if the class itself is a Relation.
             */
            $relation = $backtrace->first(function ($trace) {
                return Arr::get($trace, 'function') === 'getRelationValue' || Arr::get($trace, 'class') === Relation::class;
            });

            // We try to access a relation
            if (is_array($relation) && isset($relation['object'])) {
                if ($relation['class'] === Relation::class) {
                    $model = get_class($relation['object']->getParent());
                    $relationName = get_class($relation['object']->getRelated());
                    $relatedModel = $relationName;
                } else {
                    $model = get_class($relation['object']);
                    $relationName = $relation['args'][0];
                    $relatedModel = $relationName;
                }

                $sources = $this->findSource($backtrace);
                
                if (empty($sources)) {
                    return;
                }

                $key = md5($query->sql . $model . $relationName . $sources[0]->name . $sources[0]->line);

                $count = Arr::get($this->queries, $key . '.count', 0);
                $time = Arr::get($this->queries, $key . '.time', 0);

                $this->queries[$key] = [
                    'count' => ++$count,
                    'time' => $time + $query->time,
                    'query' => $query->sql,
                    'model' => $model,
                    'relatedModel' => $relatedModel,
                    'relation' => $relationName,
                    'sources' => $sources
                ];
            }
        }
    }

    protected function findSource($stack)
    {
        $sources = [];

        foreach ($stack as $index => $trace) {
            $sources[] = $this->parseTrace($index, $trace);
        }

        return array_values(array_filter($sources));
    }

    public function parseTrace($index, array $trace)
    {
        $frame = (object)[
            'index' => $index,
            'name' => null,
            'line' => isset($trace['line']) ? $trace['line'] : '?',
        ];

        if (isset($trace['class']) &&
            isset($trace['file']) &&
            !$this->fileIsInExcludedPath($trace['file'])
        ) {
            $frame->name = $this->normalizeFilename($trace['file']);

            return $frame;
        }

        return false;
    }

    /**
     * Check if the given file is to be excluded from analysis
     *
     * @param string $file
     * @return bool
     */
    protected function fileIsInExcludedPath($file)
    {
        $excludedPaths = [
            '/vendor/laravel/framework/src/Illuminate/Database',
            '/vendor/laravel/framework/src/Illuminate/Events',
        ];

        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($excludedPaths as $excludedPath) {
            if (strpos($normalizedPath, $excludedPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shorten the path by removing the relative links and base dir
     *
     * @param string $path
     * @return string
     */
    protected function normalizeFilename($path): string
    {
        if (file_exists($path)) {
            $path = realpath($path);
        }

        return str_replace(base_path(), '', $path);
    }

    public function getDetectedQueries(): Collection
    {
        $exceptions = config('querydetector.except', []);

        $queries = $this->queries
            ->values();

        foreach ($exceptions as $parentModel => $relations) {
            foreach ($relations as $relation) {
                $queries = $queries->reject(function ($query) use ($relation, $parentModel) {
                    return $query['model'] === $parentModel && $query['relatedModel'] === $relation;
                });
            }
        }

        $queries = $queries->where('count', '>', config('querydetector.threshold', 1))->values();

        if ($queries->isNotEmpty()) {
            event(new QueryDetected($queries));
        }

        return $queries;
    }

    protected function getOutputTypes()
    {
        $outputTypes = config('querydetector.output');

        if (!is_array($outputTypes)) {
            $outputTypes = [$outputTypes];
        }

        return $outputTypes;
    }

    protected function applyOutput(Response $response)
    {
        foreach ($this->getOutputTypes() as $type) {
            app($type)->output($this->getDetectedQueries(), $response);
        }
    }

    public function output($request, $response)
    {
        if ($this->getDetectedQueries()->isNotEmpty()) {
            $this->applyOutput($response);
        }

        return $response;
    }

    public function emptyQueries()
    {
        $this->queries = Collection::make();
    }
}
