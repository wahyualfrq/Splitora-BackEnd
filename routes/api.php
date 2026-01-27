<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SplitoraController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/process', [SplitoraController::class, 'process']);
Route::post('/clear-tmp', [SplitoraController::class, 'clearTmp']);