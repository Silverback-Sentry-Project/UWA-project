<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'SilverBack Sentry API',
        'status' => 'ok',
    ]);
});