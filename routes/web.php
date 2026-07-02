<?php

use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

// No login UI exists yet in this application. This route only prevents the
// `auth` middleware below from throwing a route-not-found error when an
// unauthenticated request is redirected; it is not a real login flow.
Route::get('/login', function () {
    return response('Authentication is not implemented yet for this application.', 401);
})->name('login');

// Grouped under /api so unauthenticated/validation errors render as JSON
// (see the shouldRenderJsonWhen('api/*') rule in bootstrap/app.php) while
// still running through the session-based `web` middleware group for auth
// and CSRF, since this app has no token-based API authentication yet.
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
});
