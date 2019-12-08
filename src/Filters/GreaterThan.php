<?php

namespace Datatable\Filters;

use Datatable\Filter;

class GreaterThan extends Filter
{
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
        return $query->where($column, '>=', $value);
    }
}
