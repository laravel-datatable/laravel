<?php

use Illuminate\Http\Request;

Route::prefix('/laravel-datatable')->group(function () {
    Route::post('/', function (Request $request) {
        $class = $request->get('query');

        return (new $class)->get();
    });

    Route::post('/resolveFilter', function (Request $request) {
        $filter = $request->get('filter');

        return (new $filter)->resolve($request->get('search'));
    });
});
