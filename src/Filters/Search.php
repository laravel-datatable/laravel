<?php

namespace Datatable\Filters;

use Closure;
use Datatable\Filter;

class Search extends Filter
{
    /**
     * Holding the search closure.
     *
     * @var Closure
     */
    protected $searchClosure;

    /**
     * Perorm a search query and return the results.
     *
     * @param  Closure $callback
     * @return ???
     */
    public function search(Closure $callback)
    {
        $this->searchClosure = $callback;

        return $this;
    }

    /**
     * Perform the actual search and return the results.
     *
     * @param  string $searchQuery
     * @return array
     */
    public function performSearch($searchQuery)
    {
        if (! is_string($searchQuery)) {
            return [];
        }

        return call_user_func_array($this->searchClosure, [$searchQuery]);
    }

    /**
     * Perform the filtering on the query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string $column
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Builder
     */
    public function execute($query, $column, $value)
    {
        return $query->where($column, $value);
    }
}
