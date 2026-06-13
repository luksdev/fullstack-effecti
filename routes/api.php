<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('customers', CustomerController::class)->names('api.customers');
Route::apiResource('services', ServiceController::class)->names('api.services');
