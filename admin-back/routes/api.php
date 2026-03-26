<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Config\CategoryController;
use App\Http\Controllers\Config\ProviderController;
use App\Http\Controllers\Config\SucursalController;
use App\Http\Controllers\Config\UnitController;
use App\Http\Controllers\Config\UnitConversionController;
use App\Http\Controllers\Config\WarehouseController;
use App\Http\Controllers\Kardex\KardexProductController;
use App\Http\Controllers\Kpi\KpiController;
use App\Http\Controllers\Product\ConversionController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductWalletController;
use App\Http\Controllers\Product\ProductWarehouseController;
use App\Http\Controllers\Puchase\PuchaseController;
use App\Http\Controllers\Puchase\PuchaseDetailController;
use App\Http\Controllers\Refurbish\RefurbishController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Sale\RefoundProductController;
use App\Http\Controllers\Sale\SaleController;
use App\Http\Controllers\Sale\SaleDetailController;
use App\Http\Controllers\Sale\SalePaimentController;
use App\Http\Controllers\Transport\TransportController;
use App\Http\Controllers\Transport\TransportDetailController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\CRM\LeadController;
use App\Http\Controllers\CRM\OpportunityController;
use App\Http\Controllers\CRM\PipelineStageController;
use App\Http\Controllers\CRM\CrmActivityController;
use App\Http\Controllers\CRM\LeadConversionController;
use App\Http\Controllers\Audit\AuditExportController;
use App\Http\Controllers\Audit\AuditLogController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group([
    // 'middleware' => 'api',
    'prefix' => 'auth',
    // 'middleware' => ['auth:api', 'permission:publish articles'],
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
    Route::post('/2fa/verify', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:mfa_challenge')->name('2fa.verify');
    Route::post('/2fa/recovery', [AuthController::class, 'verifyTwoFactorRecoveryCode'])->middleware('throttle:mfa_challenge')->name('2fa.recovery');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
    Route::post('/profile/update', [AuthController::class, 'updateProfile'])->middleware(['auth:api', 'throttle:mfa_settings'])->name('profile.update');
});


Route::group([
    'middleware' => ['auth:api', 'audit.route'],
], function ($router) {
    Route::prefix('auth/2fa')->group(function () {
        Route::get('/status', [TwoFactorController::class, 'status'])->middleware('throttle:mfa_settings');
        Route::post('/setup/init', [TwoFactorController::class, 'init'])->middleware('throttle:mfa_settings');
        Route::post('/setup/verify', [TwoFactorController::class, 'verifySetup'])->middleware('throttle:mfa_settings');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:mfa_settings');
        Route::post('/recovery/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])->middleware('throttle:mfa_settings');
    });

    Route::resource('role', RoleController::class);

    Route::get('users/config', [UserController::class, 'config']);
    Route::post('users/{id}', [UserController::class, 'update']);
    Route::resource('users', UserController::class);

    Route::group(['middleware' => ['permission:settings']], function(){
        Route::resource('sucursales', SucursalController::class);
        Route::resource('warehouses', WarehouseController::class);
        Route::post('categories/{id}', [CategoryController::class, 'update']);
        Route::resource('categories', CategoryController::class);
        Route::post('providers/{id}', [ProviderController::class, 'update']);
        Route::resource('providers', ProviderController::class);
        Route::resource('units', UnitController::class);
        Route::resource('unit-conversions', UnitConversionController::class);
    });

    Route::get('products/config', [ProductController::class, 'config']);
    Route::get('products/search_product', [ProductController::class, 'searchProduct']);
    Route::post('products/index', [ProductController::class, 'index']);
    Route::post('products/import-excel', [ProductController::class, 'import_excel']);
    Route::post('products/{id}', [ProductController::class, 'update']);
    Route::resource('products', ProductController::class);

    Route::group(['middleware' => ['permission:show_inventory_product']], function(){
        Route::resource('product-warehouse', ProductWarehouseController::class);
    });

    Route::group(['middleware' => ['permission:show_wallet_price_product']], function(){
        Route::resource('product-wallet', ProductWalletController::class);
    });

    Route::resource('clients', ClientController::class);

    Route::post('sales/index', [SaleController::class, 'index']);
    Route::post('stock-attention-detail', [SaleController::class, 'stockAttentionDetails']);
    Route::get('sales/config', [SaleController::class, 'config']);
    Route::get('sales/search_client', [SaleController::class, 'searchClient']);
    Route::resource('sales', SaleController::class);
    Route::resource('sale-details', SaleDetailController::class);
    Route::resource('sale-payments', SalePaimentController::class);
    
    Route::group(['middleware' => ['permission:return']], function(){
        Route::post('refound-products/index', [RefoundProductController::class, 'index']);
        Route::get('refound-products/search-sale/{id}', [RefoundProductController::class, 'searchSale']);
        Route::resource('refound-products', RefoundProductController::class);
    });

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

    Route::group(['middleware' => ['permission:conversions']], function(){
        Route::post('conversions/index', [ConversionController::class, 'index']);
        Route::resource('conversions', ConversionController::class);
    });

    Route::group(['middleware' => ['permission:kardex']], function(){
        Route::post('kardex-product', [KardexProductController::class, 'kardexProduct']);
    });

    Route::group(['prefix' => 'kpi', 'middleware' => ['permission:dashboard']], function(){
        Route::post('information-general',  [KpiController::class, 'information_general']);
        Route::post('asesor-most-sale',     [KpiController::class, 'asesorMostSale']);
        Route::post('sales-total-payment',  [KpiController::class, 'salesTotalPayment']);
        Route::post('sucursales-report-sales', [KpiController::class, 'sucursalesReportSales']);
        Route::post('client-most-sale',     [KpiController::class, 'clientMostSale']);
        Route::post('sales-x-month-year',  [KpiController::class, 'salesXMonthYear']);
        Route::post('category-most-sales', [KpiController::class, 'categoryMostSales']);
    });

    // Módulo de Reacondicionamiento
    Route::prefix('refurbish')->group(function () {
        Route::get('/equipment/{id}', [RefurbishController::class, 'show'])->middleware('permission:list_refurbish');
        Route::post('/start/{id}', [RefurbishController::class, 'start'])->middleware('permission:register_refurbish');
        Route::post('/add-component', [RefurbishController::class, 'addComponent'])->middleware('permission:edit_refurbish');
        Route::post('/remove-component', [RefurbishController::class, 'removeComponent'])->middleware('permission:edit_refurbish');
        Route::post('/remove-unregistered', [RefurbishController::class, 'removeUnregistered'])->middleware('permission:edit_refurbish');
        Route::post('/finish/{id}', [RefurbishController::class, 'finish'])->middleware('permission:edit_refurbish');
    });

    // Módulo CRM
    Route::prefix('crm')->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->middleware('permission:list_lead');
        Route::post('leads', [LeadController::class, 'store'])->middleware('permission:register_lead');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->middleware('permission:list_lead');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->middleware('permission:edit_lead');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:delete_lead');
        Route::post('leads/{lead}/convert', [LeadConversionController::class, 'convert'])->middleware('permission:convert_lead');

        Route::get('opportunities', [OpportunityController::class, 'index'])->middleware('permission:list_opportunity');
        Route::post('opportunities', [OpportunityController::class, 'store'])->middleware('permission:register_opportunity');
        Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show'])->middleware('permission:list_opportunity');
        Route::put('opportunities/{opportunity}', [OpportunityController::class, 'update'])->middleware('permission:edit_opportunity');
        Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroy'])->middleware('permission:delete_opportunity');
        Route::post('opportunities/{opportunity}/change-stage', [OpportunityController::class, 'changeStage'])->middleware('permission:edit_opportunity');

        Route::get('pipeline-stages', [PipelineStageController::class, 'index'])->middleware('permission:list_opportunity');
        Route::post('pipeline-stages', [PipelineStageController::class, 'store'])->middleware('permission:edit_opportunity');
        Route::put('pipeline-stages/{stage}', [PipelineStageController::class, 'update'])->middleware('permission:edit_opportunity');
        Route::delete('pipeline-stages/{stage}', [PipelineStageController::class, 'destroy'])->middleware('permission:edit_opportunity');
        Route::post('pipeline-stages/reorder', [PipelineStageController::class, 'reorder'])->middleware('permission:edit_opportunity');

        Route::get('activities', [CrmActivityController::class, 'index'])->middleware('permission:list_crm_activity');
        Route::post('activities', [CrmActivityController::class, 'store'])->middleware('permission:register_crm_activity');
        Route::put('activities/{activity}', [CrmActivityController::class, 'update'])->middleware('permission:edit_crm_activity');
        Route::delete('activities/{activity}', [CrmActivityController::class, 'destroy'])->middleware('permission:delete_crm_activity');
    });


    Route::get("products-excel",    [ProductController::class, 'download_excel']);
    Route::get("sales-excel",       [SaleController::class, 'download_excel']);
    Route::get("sales-pdf/{id}",    [SaleController::class, 'sale_pdf']);
    Route::get("pushases-pdf/{id}", [PuchaseController::class, 'pushases_pdf']);
    Route::get("transport-pdf/{id}", [TransportController::class, 'transports_pdf']);

    Route::prefix('audit')->group(function () {
        Route::post('navigation', [AuditLogController::class, 'navigation'])->name('audit.navigation');

        Route::group(['middleware' => ['permission:view_audit_logs']], function () {
            Route::get('logs', [AuditLogController::class, 'index'])->name('audit.logs.index');
            Route::get('logs/filters', [AuditLogController::class, 'filters'])->name('audit.logs.filters');
            Route::get('logs/{id}', [AuditLogController::class, 'show'])->name('audit.logs.show');
        });

        Route::get('logs/export', [AuditExportController::class, 'export'])
            ->middleware('permission:export_audit_logs')
            ->name('audit.logs.export');
    });
    
});
