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
use App\Http\Controllers\Kardex\KardexProductController;
use App\Http\Controllers\Product\ConversionController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductWalletController;
use App\Http\Controllers\Product\ProductWarehouseController;
use App\Http\Controllers\Puchase\PuchaseController;
use App\Http\Controllers\Puchase\PuchaseDetailController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Sale\RefoundProductController;
use App\Http\Controllers\Sale\SaleController;
use App\Http\Controllers\Sale\SaleDetailController;
use App\Http\Controllers\Sale\SalePaimentController;
use App\Http\Controllers\Transport\TransportController;
use App\Http\Controllers\Transport\TransportDetailController;
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
    Route::post('stock-attention-detail', [SaleController::class, 'stockAttentionDetails']);
    Route::get('sales/config', [SaleController::class, 'config']);
    Route::get('sales/search_client', [SaleController::class, 'searchClient']);
    Route::resource('sales', SaleController::class);
    Route::resource('sale-details', SaleDetailController::class);
    Route::resource('sale-payments', SalePaimentController::class);
    
    Route::post('refound-products/index', [RefoundProductController::class, 'index']);
    Route::get('refound-products/search-sale/{id}', [RefoundProductController::class, 'searchSale']);
    Route::resource('refound-products', RefoundProductController::class);

    Route::get('pushases/config', [PuchaseController::class, 'config']);
    Route::post('pushases/index', [PuchaseController::class, 'index']);
    Route::resource('pushases', PuchaseController::class);
    Route::post('pushase-details/attention', [PuchaseDetailController::class, 'attention']);
    Route::resource('pushase-details', PuchaseDetailController::class);

    Route::get('transports/config', [TransportController::class, 'config']);
    Route::post('transports/index', [TransportController::class, 'index']);
    Route::resource('transports', TransportController::class);
    Route::resource('transport-details', TransportDetailController::class);
    Route::post('transport-details/attention-exit', [TransportDetailController::class, 'attentionExit']);
    Route::post('transport-details/attention-delivery', [TransportDetailController::class, 'attentionDelivery']);

    Route::post('conversions/index', [ConversionController::class, 'index']);
    Route::resource('conversions', ConversionController::class);

    Route::post('kardex-product', [KardexProductController::class, 'kardexProduct']);
});


Route::get("products-excel",    [ProductController::class, 'download_excel']);
Route::get("sales-excel",       [SaleController::class, 'download_excel']);
Route::get("sales-pdf/{id}",    [SaleController::class, 'sale_pdf']);
Route::get("pushases-pdf/{id}", [PuchaseController::class, 'pushases_pdf']);
Route::get("transport-pdf/{id}", [TransportController::class, 'transports_pdf']);
