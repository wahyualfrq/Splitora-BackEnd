<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'splitora',
        'status'  => 'ok',
    ], 200);
});
