<?php

use App\Http\Controllers\Ajax\GroupChart;
use App\Http\Controllers\Ajax\ImageIntensity;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::post('upload', [ImageController::class, 'upload']);

Route::get('/image/{id}', [ImageController::class, 'index']);

Route::get('/ajax/intensity/{id}/{m}_{n}', [ImageIntensity::class, 'get']);

Route::post('/ajax/chart', [GroupChart::class, 'get']);