<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Config\SucursalController;
use App\Http\Controllers\Roles\RoleController;
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
});
