<?php

namespace Datatable;

use DB;
use Exception;
use Validator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

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

        if ($this->filter['enabled'] && $this->request->filled('filters') && !empty($this->request->get('filters'))) {
            $this->filterQuery();
        }

        // $this->query = $this->query->whereNull('deleted_at');

        $this->query = $this->query();

        if (count($this->selects)) {
            $this->performSelectsOnQuery();
        }

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

    public function response($items = [])
    {
        return [
            'filter' => ['enabled' => $this->filter['enabled'], 'filters' => $this->getAvailableFiltersFormatted()],
            'pagination' => $this->pagination['enabled'],
            'searchable' => $this->search['enabled'],
            'sort' => $this->sort,
            'items' => $items,
            'requiresSearch' => $this->requiresSearch,
            'requiresSearchLength' => $this->requiresSearchLength,
        ];
    }

    public function getAvailableFiltersFormatted()
    {
        $availableFilters = [];

        if (!$this->filter['enabled'] || !count($this->filter['filters'])) {
            return $availableFilters;
        }

        foreach ($this->filter['filters'] as $column => $filter) {
            $class = resolve($filter);

            $availableFilters[$column] = $class->toArray();
        }

        return $availableFilters;
    }

    public function injectRouteParameters()
    {
        foreach ($this->request->get('inject') as $name => $value) {
            $class = $this->inject()[$name];

            $this->{$name} = resolve($class)->find($value);
        }
    }

    public function sortQuery()
    {
        $currentSorting = $this->request->sort;

        $column = $currentSorting['column'];
        $direction = $currentSorting['direction'];

        if ($column && $direction) {
            $this->query->orderBy($column, $direction);
        }
    }

    public function performSelectsOnQuery()
    {
        $formatedSelects = [];

        foreach ($this->selects as $name => $alias) {
            $formatedSelects[] = $alias.' AS '.$name;
        }

        $this->query = $this->query->select($formatedSelects);
    }

    public function searchQuery($searchString)
    {
        $fields = count($this->selects) ? $this->nameFieldsToAlias($this->search['fields']) : $this->search['fields'];

        $this->query = $this->query->whereLike($fields, $searchString);
    }

    public function nameFieldsToAlias($fields) {
        $formatedFields = [];

        foreach ($fields as $field) {
            $formatedFields[] = $this->selects[$field];
        }

        return $formatedFields;
    }

    /**
     * Filter the query.
     *
     * @return void
     */
    public function filterQuery()
    {
        foreach ($this->getAvailableFiltersFormatted() as $column => $settings) {
            $filters = $this->request->get('filters');

            // If the filter is not filtered or has an empty value just continue.
            if (! isset($filters[$column]) || is_null($filters[$column]['value'])) {
                continue;
            }

            if (isset($settings['validation'])) {
                $validator = Validator::make($filters, [
                    $column => $settings['validation'],
                ]);

                if ($validator->fails()) {
                    continue;
                }
            }

            $selectedColumn = empty($this->selects) ? $column : $this->selects[$column];

            if ($settings['type'] === 'options') {
                $this->query = $this->query->whereIn($selectedColumn, $filters[$column]['value']);
                continue;
            }

            if ($settings['type'] === 'from_date' && ! empty($filters[$column])) {
                if (strpos($filters[$column], ',') !== false) {
                    $from = date(explode(',', $filters[$column])[0]);
                    $to = date(explode(',', $filters[$column])[1]);

                    $this->query = $this->query->whereDate($selectedColumn, '>=', $from)->whereDate($selectedColumn, '<=', $to);
                } else {
                    $this->query = $this->query->whereDate($selectedColumn, '>=', $filters[$column]);
                }
                continue;
            }

            if ($settings['type'] === 'greater_than') {
                $this->query = $this->query->where($selectedColumn, '>=', $filters[$column]);
                continue;
            }

            if ($settings['type'] === 'search') {
                $this->query = $this->query->where($selectedColumn, $filters[$column]);
                continue;
            }

            if ($settings['type'] === 'relation') {
                $this->query = $this->query->where($selectedColumn, $filters[$column]['value'][$settings['relation']['search_by']]);
                continue;
            }
        }
    }

    abstract function query();
}
