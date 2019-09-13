<?php

namespace Datatable;

use Illuminate\Contracts\Support\Arrayable;

abstract class Filter implements Arrayable
{
    /**
     * Serialize the filter to array format.
     *
     * @return array
     */
    public function toArray() {
        $array = [];

        $array['label'] = $this->label();
        $array['type'] = $this->type();
        $array['filter'] = get_called_class();
        $array['can'] = $this->can();

        if ($array['type'] === 'options') {
            $array['options'] = $this->options();
        }

        if ($array['type'] === 'relation') {
            $array['relation'] = $this->relation();
        }

        return $array;
    }

    /**
     * Retrive the filter label.
     *
     * @return string
     */
    abstract public function label();

    /**
     * Determines the filter type. Available: `options`, `relation`, `search`, `greater_than`, `from_date`
     *
     * @return string
     */
    abstract public function type();

    /**
     * Determine if the current authenticated user can use the filter.
     *
     * @return boolean
     */
    public function can()
    {
        return true;
    }
}
