<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'WildWatch API',
        'status' => 'ok',
    ]);
});