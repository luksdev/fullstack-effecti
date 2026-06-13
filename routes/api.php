<?php

use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ContractItemController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('customers', CustomerController::class)->names('api.customers');
Route::apiResource('services', ServiceController::class)->names('api.services');

Route::apiResource('contracts', ContractController::class)->names('api.contracts');
Route::apiResource('contracts.items', ContractItemController::class)
    ->only(['store', 'destroy'])
    ->names('api.contracts.items');
