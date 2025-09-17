<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Config\CategoryController;
use App\Http\Controllers\Config\ProviderController;
use App\Http\Controllers\Config\SucursalController;
use App\Http\Controllers\Config\UnitController;
use App\Http\Controllers\Config\UnitConversionController;
use App\Http\Controllers\Config\WarehouseController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductWalletController;
use App\Http\Controllers\Product\ProductWarehouseController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Sale\SaleController;
use App\Http\Controllers\Sale\SaleDetailController;
use App\Http\Controllers\Sale\SalePaimentController;
use App\Http\Controllers\User\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group([
    // 'middleware' => 'api',
    'prefix' => 'auth',
    // 'middleware' => ['auth:api', 'permission:publish articles'],
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
});


Route::group([
    'middleware' => ['auth:api'],
], function ($router) {
    Route::resource('role', RoleController::class);

    Route::get('users/config', [UserController::class, 'config']);
    Route::post('users/{id}', [UserController::class, 'update']);
    Route::resource('users', UserController::class);

    Route::resource('sucursales', SucursalController::class);

    Route::resource('warehouses', WarehouseController::class);

    Route::post('categories/{id}', [CategoryController::class, 'update']);
    Route::resource('categories', CategoryController::class);

    Route::post('providers/{id}', [ProviderController::class, 'update']);
    Route::resource('providers', ProviderController::class);

    Route::resource('units', UnitController::class);

    Route::resource('unit-conversions', UnitConversionController::class);

    Route::get('products/config', [ProductController::class, 'config']);
    Route::get('products/search_product', [ProductController::class, 'searchProduct']);
    Route::post('products/index', [ProductController::class, 'index']);
    Route::post('products/import-excel', [ProductController::class, 'import_excel']);
    Route::post('products/{id}', [ProductController::class, 'update']);
    Route::resource('products', ProductController::class);

    Route::resource('product-warehouse', ProductWarehouseController::class);
    Route::resource('product-wallet', ProductWalletController::class);

    Route::resource('clients', ClientController::class);

    Route::post('sales/index', [SaleController::class, 'index']);
    Route::post('stock-attention-detail', [SaleController::class, 'stockAttentionDetailx']);
    Route::get('sales/config', [SaleController::class, 'config']);
    Route::get('sales/search_client', [SaleController::class, 'searchClient']);
    Route::resource('sales', SaleController::class);
    Route::resource('sale-details', SaleDetailController::class);
    Route::resource('sale-payments', SalePaimentController::class);
});


Route::get("products-excel",    [ProductController::class, 'download_excel']);
Route::get("sales-excel",       [SaleController::class, 'download_excel']);
