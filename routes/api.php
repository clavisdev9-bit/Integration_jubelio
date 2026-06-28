<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Odoo\OdooDebugController;
use App\Http\Controllers\Api\Jubelio\JubelioProductController;
use App\Http\Controllers\Api\Odoo\OdooProductController;
use App\Http\Controllers\Api\Reports\Master\ProductsController;
use App\Http\Controllers\Api\Reports\Master\SupplierController;
use App\Http\Controllers\Api\Reports\Core\PurchaseOrdersController;
use App\Http\Controllers\Api\Reports\Core\SalesOrdersController;




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
Route::get('/master-supplier', [SupplierController::class, 'index'])->name('api.index');


// List Purchase Order Route
Route::prefix('purchase-orders')->group(function () {
    // all data po
    Route::get('/', [PurchaseOrdersController::class, 'index']);
    
    // with parameter (id, no ref, purchase order name)
    Route::get('/id/{id}', [PurchaseOrdersController::class, 'showById']);
    Route::get('/number/{purchaseorder_no}', [PurchaseOrdersController::class, 'showByNumber']);
    Route::get('/ref/{ref_no}', [PurchaseOrdersController::class, 'showByRef'])
                ->where('ref_no', '.*');
    // Dashboard 
    Route::get(
        '/dashboard',
        [PurchaseOrdersController::class, 'dashboard']
    );            

});


// List Sales Order Route
Route::prefix('sales-orders')->group(function () {

    Route::get('/', [SalesOrdersController::class, 'index']);

    Route::get('/id/{id}', [SalesOrdersController::class, 'showById']);

    Route::get('/number/{salesorder_no}', [SalesOrdersController::class, 'showByNumber']);

    Route::get('/ref/{ref_no}', [SalesOrdersController::class, 'showByRef'])
        ->where('ref_no', '.*');

    // Dashboard 
    Route::get(
        '/dashboard',
        [SalesOrdersController::class, 'dashboard']
    );  

});



