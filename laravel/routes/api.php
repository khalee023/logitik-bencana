<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\SarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Peta publik — GeoJSON data endpoint
Route::get('/map-data', [PublicController::class, 'mapData'])->name('api.map-data');


