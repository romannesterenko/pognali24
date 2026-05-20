<?php

use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/search', [TripController::class, 'search']);
