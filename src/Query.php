<?php

namespace Datatable;

use DB;
use Exception;
use Validator;
use Illuminate\Database\Query\Builder;

abstract class Query
{
    /**
     * The database table to be used by the query builder.
     *
     * @var string
     */
    protected $table;

    /**
     * Pagination settings.
     *
     * @var array
     */
    protected $pagination = [
        'enabled' => false,
    ];

    /**
     * Sortings settings.
     *
     * @var array
     */
    protected $sort = [
        'enabled' => false,
        'fields' => [],
    ];

    /**
     * Search settings.
     *
     * @var array
     */
    protected $search = [
        'enabled' => false,
        'fields' => [],
    ];

    /**
     * Filter settings.
     *
     * @var array
     */
    protected $filter = [
        'enabled' => false,
        'filters' => [],
    ];

    /**
     * Query selects with alias.
     *
     * @var array
     */
    protected $selects = [];

    protected $requiresSearch = false;
    protected $requiresSearchLength = 1;

    /**
     * Create a new Query instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->query = DB::table($this->table);
        $this->request = request();
    }

    public function get()
    {
        if (
            $this->requiresSearch &&
            (!$this->request->filled('searchString') ||
            strlen($this->request->get('searchString')) < $this->requiresSearchLength)
        ) {
            return $this->response();
        }

        if (method_exists($this, 'inject')) {
            $this->injectRouteParameters();
        }

        if ($this->sort['enabled'] && $this->request->has('sort')) {
            $this->sortQuery();
        }

        if ($this->request->filled('filters') && !empty($this->request->get('filters'))) {
            $this->filterQuery();
        }

        // $this->query = $this->query->whereNull('deleted_at');

        $this->query = $this->query();

        // @fixme
        $this->query = $this->query->select(
            collect($this->columns())->map(function ($column) {
                return $column['column'] . ' AS ' . $column['column'];
            })->toArray()
        );

        if ($this->search['enabled'] && $this->request->filled('searchString')) {
            $this->searchQuery($this->request->get('searchString'));
        }

        if (method_exists($this, 'transform')) {
            $this->query = $this->transform($this->query->get());
        }

        $items = $this->pagination['enabled'] ?
                $this->query->paginate($this->request->get('limit', 10)) :
                $this->query->get();

        return $this->response($items);
    }

    /**
     * Return the queried response to the datatable to render.
     *
     * @param  array  $items
     * @return array
     */
    public function response($items = [])
    {
        // Attach the query class namespace to the filter.
        $filters = collect(
            method_exists($this, 'filters') ? $this->filters() : []
        )->map(function ($filter) {
            $filter->withMeta(['query' => get_called_class()]);

            return $filter;
        });

        return [
            'filters' => $filters,
            'pagination' => $this->pagination['enabled'],
            'searchable' => $this->search['enabled'],
            'sort' => $this->sort,
            'items' => $items,
            'requiresSearch' => $this->requiresSearch,
            'requiresSearchLength' => $this->requiresSearchLength,
            'columns' => $this->columns(),
        ];
    }

    /**
     * FIXME
     */
    public function injectRouteParameters()
    {
        foreach ($this->request->get('inject') as $name => $value) {
            $class = $this->inject()[$name];

            $this->{$name} = resolve($class)->find($value);
        }
    }

    /**
     * Perform requested sortings against the current query.
     *
     * @return void
     */
    public function sortQuery()
    {
        $currentSorting = $this->request->sort;

        $column = $currentSorting['column'];
        $direction = $currentSorting['direction'];

        if ($column && $direction) {
            $this->query->orderBy($column, $direction);
        }
    }

    /**
     * Perform a search query against the current query.
     *
     * @param  string $searchString
     * @return void
     */
    public function searchQuery($searchString)
    {
        $this->query = $this->query->whereLike($this->search['fields'], $searchString);
    }

    /**
     * Filter the query.
     *
     * @return void
     */
    public function filterQuery()
    {
        // Start with iterating through all the available
        // filters for this current datatable configuration.
        foreach ($this->filters() as $filter) {
            // Then we iterate through all the database columns
            // that this filter is able to filter against.
            foreach ($filter->getColumns() as $column) {
                // Continue if the given filter is not in the request.
                if (! isset($this->request->filters[$filter->getName()])) {
                    continue;
                }

                $value = $this->request->filters[$filter->getName()];

                // Ignore if the value is empty.
                if (empty($value)) {
                    continue;
                }

                $this->query = $filter->execute($this->query, $column, $value);
            }
        }
    }

    abstract function query();
}
