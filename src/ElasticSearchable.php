<?php

namespace ScoutEngines\Elasticsearch;

use Laravel\Scout\Searchable;
use ScoutEngines\Elasticsearch\Builder as ElasticBuilder;

trait ElasticSearchable
{
    use Searchable;

    public $elasticQuery;

    public $elasticSource;

    /**
     * Use the first defined index in config
     *
     * @return string
     */
    public function searchableWithin()
    {
        return current(array_keys(config('elasticsearch.indices')));
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * Overrides Laravel\Scout\Searchable::search() to return an instance
     * of the custom ScoutEngines\Elasticsearch\Builder class.
     *
     * @param  string  $query
     * @param  Closure  $callback
     * @return \Laravel\Scout\Builder
     */
    public static function search($query, $callback = null)
    {
        return new ElasticBuilder(new static, $query, $callback);
    }

    /**
     * Search; Elasticsearch style
     *
     * @param $method
     * @param $query
     * @param array|null $params
     * @return ElasticBuilder
     */
    public static function elasticSearch($method, $query, array $params = null)
    {
        $model = new static;

        $model->elasticQuery = [
            'method' => $method,
            'params' => $params
        ];

        return new ElasticBuilder($model, $query);
    }
}
