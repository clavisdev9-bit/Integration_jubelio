<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Odoo\OdooDebugController;
use App\Http\Controllers\Api\Jubelio\JubelioProductController;
use App\Http\Controllers\Api\Odoo\OdooProductController;
use App\Http\Controllers\Api\Reports\Master\ProductsController;
use App\Http\Controllers\Api\Reports\Master\SupplierController;
use App\Http\Controllers\Api\Reports\Core\PurchaseOrdersController;

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
Route::get('/master-supplier', [SupplierController::class, 'index'])->name('api.index');
// Route::get('/purchase-order', [PurchaseOrdersController::class, 'index'])->name('api.index');


// List Purchase Order
Route::prefix('purchase-orders')->group(function () {
    // all data po
    Route::get('/', [PurchaseOrdersController::class, 'index']);
    
    // with parameter (id, no ref, purchase order name)
    Route::get('/id/{id}', [PurchaseOrdersController::class, 'showById']);
    Route::get('/number/{purchaseorder_no}', [PurchaseOrdersController::class, 'showByNumber']);
    Route::get('/ref/{ref_no}', [PurchaseOrdersController::class, 'showByRef'])
                ->where('ref_no', '.*');
    Route::get(
        '/dashboard',
        [PurchaseOrdersController::class, 'dashboard']
    );            

});


