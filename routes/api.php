<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Odoo\OdooDebugController;
use App\Http\Controllers\Api\Jubelio\JubelioProductController;
use App\Http\Controllers\Api\Odoo\OdooProductController;
use App\Http\Controllers\Api\Reports\Master\ProductsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// route jubelio
Route::prefix('jubelio')->group(function () {
    Route::get(
        '/products',
        [JubelioProductController::class, 'index']
    );

});


Route::prefix('odoo')->group(function () {

    Route::get(
        '/products',
        [OdooProductController::class, 'index']
    );

    Route::post(
    '/debug',
    [OdooDebugController::class, 'execute']
);

});


// start route API for External  (reports)
Route::get('/master-products', [ProductsController::class, 'index'])->name('api.index');



