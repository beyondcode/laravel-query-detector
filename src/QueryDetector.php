<?php

namespace BeyondCode\QueryDetector;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\Relations\Relation;
use BeyondCode\QueryDetector\Events\QueryDetected;
use BeyondCode\QueryDetector\Concerns\Bootable;
use BeyondCode\QueryDetector\Concerns\HasContext;
use BeyondCode\QueryDetector\Concerns\InteractsWithSourceFiles;

class QueryDetector
{
    use Bootable, HasContext, InteractsWithSourceFiles;

    /** @var Collection */
    private $queries;

    public function __construct()
    {
        $this->queries = Collection::make();
    }

    protected function boot()
    {
        DB::listen(function($query) {
            $backtrace = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50));

            $this->logQuery($query, $backtrace);
        });

        foreach ($this->getOutputTypes() as $outputType) {
            app()->singleton($outputType);
            app($outputType)->boot();
        }
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
        if (! is_null($modelTrace)) {
            /*
             * Relations get resolved by either calling the "getRelationValue" method on the model,
             * or if the class itself is a Relation.
             */
            $relation = $backtrace->first(function ($trace) {
                return Arr::get($trace, 'function') === 'getRelationValue' || Arr::get($trace, 'class') === Relation::class ;
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

                $key = md5($this->context . $query->sql . $model . $relationName . $sources[0]->name . $sources[0]->line);

                $count = Arr::get($this->queries, $key.'.count', 0);
                $time = Arr::get($this->queries, $key.'.time', 0);

                $this->queries[$key] = [
                    'count' => ++$count,
                    'time' => $time + $query->time,
                    'query' => $query->sql,
                    'model' => $model,
                    'relatedModel' => $relatedModel,
                    'relation' => $relationName,
                    'context' => $this->context,
                    'sources' => $sources
                ];
            }
        }
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

        if (! is_array($outputTypes)) {
            $outputTypes = [$outputTypes];
        }

        return $outputTypes;
    }

    protected function applyOutput(Collection $detectedQueries, Response $response)
    {
        foreach ($this->getOutputTypes() as $type) {
            app($type)->output($detectedQueries, $response);
        }
    }

    public function output($request, $response)
    {
        $detectedQueries = $this->getDetectedQueries();

        if ($detectedQueries->isNotEmpty()) {
            $this->applyOutput($detectedQueries, $response);
        }

        return $response;
    }
}
