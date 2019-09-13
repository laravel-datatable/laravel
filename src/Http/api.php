<?php

use Illuminate\Http\Request;

Route::middleware([])->group(function () {
    Route::post('/laravel-datatable', function (Request $request) {
        $class = $request->get('query');

        return (new $class)->get();
    });

    Route::post('/laravel-datatable/resolveFilter', function (Request $request) {
        $filter = $request->get('filter');

        return (new $filter)->resolve($request->get('search'));
    });
});
