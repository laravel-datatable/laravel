<?php

namespace Datatable;

use JsonSerializable;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;

abstract class Filter implements JsonSerializable
{
    /**
     * Holding filterable columns.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Holding the filter label.
     *
     * @var string
     */
    protected $label = null;

    protected $name;

    protected $meta = [];

    /**
     * Create a new Filter instance.
     *
     * @param  $label  string
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Serialize the filter to array format.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];

        $array['name'] = $this->name;
        $array['label'] = $this->label;
        $array['component'] = $this->component();

        return array_merge($array, $this->meta);
    }

    public function component()
    {
        $className = explode('\\', get_called_class());

        return Str::replaceArray('?', [Str::kebab(end($className))], 'datatable-filter-?');
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function withMeta($array)
    {
        $this->meta = array_merge($this->meta, $array);
        return $this;
    }

    /**
     * Retrive the filter columns.
     *
     * @return string
     */
    public function columns($columns) {
        $this->columns = $columns;
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getName()
    {
        return $this->name;
    }

    public static function make($name) {
        return new static($name);
    }

    public function label($label)
    {
        $this->label = $label;
        return $this;
    }
}
