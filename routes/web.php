<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\ResolveDemoUser;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

// This demo app has no login UI and no roles. `ResolveDemoUser` resolves
// every request as a single demo user (see that class for details), so
// there is no unauthenticated state to gate here — no `auth` middleware,
// no login route. Still grouped under /api so validation errors render as
// JSON (see the shouldRenderJsonWhen('api/*') rule in bootstrap/app.php).
Route::prefix('api')->middleware(ResolveDemoUser::class)->group(function () {
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('/inventory-items', [InventoryItemController::class, 'index'])->name('inventory-items.index');
    Route::post('/inventory-items', [InventoryItemController::class, 'store'])->name('inventory-items.store');
    Route::put('/inventory-items/{inventory_item}', [InventoryItemController::class, 'update'])->name('inventory-items.update');
    Route::delete('/inventory-items/{inventory_item}', [InventoryItemController::class, 'destroy'])->name('inventory-items.destroy');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
});
