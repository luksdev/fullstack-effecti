<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('services', ServiceController::class)->except('show');

    Route::resource('contracts', ContractController::class)->except('show');
    Route::post('contracts/{contract}/items', [ContractController::class, 'storeItem'])->name('contracts.items.store');
    Route::delete('contracts/{contract}/items/{item}', [ContractController::class, 'destroyItem'])->name('contracts.items.destroy');
});
