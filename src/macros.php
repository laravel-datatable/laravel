<?php

use Illuminate\Database\Query\Builder;

Builder::macro('whereLike', function ($attributes, string $searchTerm) {
    $this->where(function (Builder $query) use ($attributes, $searchTerm) {
        foreach (array_wrap($attributes) as $attribute) {
            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
        }
    });

    return $this;
});
