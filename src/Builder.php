<?php

namespace ScoutEngines\Elasticsearch;

use BadMethodCallException;
use Closure;
use Laravel\Scout\Builder as ScoutBuilder;

class Builder extends ScoutBuilder
{
    protected $queryDsl;

    /**
     * Create a new search builder instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $query
     * @param  Closure  $callback
     * @return void
     */
    public function __construct($model, $query, $callback = null)
    {
        $this->queryDsl = new QueryDsl;

        parent::__construct($model, $query, $callback);
    }

    /**
     * Add a constraint to the search query.
     *
     * Overrides Laravel\Scout\Builder::where() to add
     * the constraint directly to $queryDsl.
     *
     * @param  string  $field
     * @param  mixed  $value
     * @return $this
     */
    public function where($field, $value = null)
    {
        $this->queryDsl->where($field, $value);

        return $this;
    }

    /**
     * Add an "order" for the search query.
     *
     * Overrides Laravel\Scout\Builder::orderBy() to add
     * the order directly to $queryDsl.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->queryDsl->orderBy($column, $direction);

        return $this;
    }

    /**
     * Get the chunked results of the search.
     *
     * @param  int $count
     * @param  callable  $callback
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function chunk($count, callable $callback)
    {
        return $this->engine()->chunk($this, $count, $callback);
    }

    /**
     * Get the Query DSL builder instance.
     *
     * @return QueryDsl
     */
    public function getQueryDsl()
    {
        return $this->queryDsl;
    }

    /**
     * Handle dynamic method calls into the builders.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->queryDsl, $method)) {
            $this->queryDsl->$method(...$parameters);
            return $this;
        }

        $className = static::class;

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
