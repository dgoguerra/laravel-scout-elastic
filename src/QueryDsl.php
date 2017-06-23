<?php

namespace ScoutEngines\Elasticsearch;

use Closure;

class QueryDsl
{
    protected $index = '_all';

    protected $type = '_all';

    protected $queryMethod = null;

    protected $queryParams = [];

    protected $queryString = '*:*';

    protected $from;

    protected $size;

    protected $wheres = [];

    protected $orders = [];

    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setMethod($method, array $params = [])
    {
        $this->queryMethod = $method;
        $this->queryParams = $params;

        return $this;
    }

    public function setQuery($queryString)
    {
        $this->queryString = $queryString;

        return $this;
    }

    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    protected function whereColumn($field, $value, $operator = 'and')
    {
        $this->wheres[] = ['type' => 'column', 'field' => $field, 'value' => $value, 'operator' => $operator];
    }

    protected function whereGroup(array $wheres, $operator = 'and')
    {
        $this->wheres[] = ['type' => 'group', 'wheres' => $wheres, 'operator' => $operator];
    }

    public function where($field, $value = null)
    {
        if ($field instanceof Closure) {
            $field($query = new self);

            $groupedWheres = $query->getWheres();

            if (count($groupedWheres)) {
                $this->whereGroup($groupedWheres);
            }
        } else {
            $this->whereColumn($field, $value);
        }

        return $this;
    }

    public function orWhere($field, $value)
    {
        $this->whereColumn($field, $value, 'or');

        return $this;
    }

    /**
     * Add an "order" for the search query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ];

        return $this;
    }

    public function getWheres()
    {
        return $this->wheres;
    }

    protected function compileWheres($wheres)
    {
        $compiled = [];

        foreach ($wheres as $where)
        {
            // current where is AND/OR group
            if ($where['type'] === 'group') {
                $subfilters = $this->compileWheres($where['wheres']);

                if (count($subfilters) === 1) {
                    $compiled[] = $subfilters[0];
                } else {
                    $section = ($where['operator'] === 'and' ? 'must' : 'should');
                    $compiled[] = ['bool' => [$section => $subfilters]];
                }
                continue;
            }

            // current where is AND column
            if ($where['operator'] === 'and') {
                $compiled[] = ['term' => [$where['field'] => $where['value']]];
                continue;
            }

            // current where is OR column
            $lastClause = array_pop($compiled);

            // if last clause was an OR group, append new term to it
            if ($lastClause && isset($lastClause['bool']['should'])) {
                $lastClause['bool']['should'][] = ['term' => [$where['field'] => $where['value']]];
                $compiled[] = $lastClause;
                continue;
            }

            // if last clause was anything else or there was no last clause,
            // create a new OR group with the current (and last if any) clause in it
            $clause = [
                'bool' => [
                    'should' => [
                        ['term' => [$where['field'] => $where['value']]]
                    ]
                ]
            ];

            if ($lastClause) {
                // prepend last clause to maintain ordering
                array_unshift($clause['bool']['should'], $lastClause);
            }

            $compiled[] = $clause;
        }

        return $compiled;
    }

    function compileSorting($orders)
    {
        return collect($orders)->map(function ($value, $key) {
            return [array_get($value, 'column') => ['order' => array_get($value, 'direction')]];
        })->values()->all();
    }

    public function build()
    {
        $queryDsl = [
            'index' => $this->index,
            'type' => $this->type,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                $this->queryMethod => array_merge([
                                    'query' => $this->queryString
                                ], $this->queryParams)
                            ]
                        ]
                    ]
                ],
                'sort' => [
                    '_score'
                ],
                'track_scores' => true
            ]
        ];

        if ($this->from) {
            $queryDsl['body']['from'] = $this->from;
        }

        if ($this->size) {
            $queryDsl['body']['size'] = $this->size;
        }

        if (count($this->wheres)) {
            $queryDsl['body']['query']['bool']['filter'] = $this->compileWheres($this->wheres);
        }

        if (count($this->orders)) {
            $queryDsl['body']['sort'] = array_merge(
                $queryDsl['body']['sort'],
                $this->compileSorting($this->orders)
            );
        }

        return $queryDsl;
    }


}
