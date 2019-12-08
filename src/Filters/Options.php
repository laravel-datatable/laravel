<?php

namespace Datatable\Filters;

use Datatable\Filter;

class Options extends Filter
{
    /**
     * Set the available options.
     *
     * @param  array $options
     * @return \Datatable\Filters\OptionsFilter
     */
    public function options($options)
    {
        return $this->withMeta(['options' => $options]);
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
        return $query->whereIn($column, $value);
    }
}
