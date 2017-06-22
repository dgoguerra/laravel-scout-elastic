<?php

namespace ScoutEngines\Elasticsearch;

use Laravel\Scout\Builder as ScoutBuilder;

class Builder extends ScoutBuilder
{
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
}
