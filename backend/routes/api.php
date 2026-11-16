<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\ProductPriceAlertController;
use App\Http\Controllers\PurchaseIntentController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/admin/register', [AdminController::class, 'register']);
Route::post('/admin/login', [AdminController::class, 'login']);
Route::post('/admin/logout', [AdminController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/admin/user', [AdminController::class, 'user'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Product Price Alerts Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/product-price-alerts', [ProductPriceAlertController::class, 'index']);
    Route::post('/product-price-alerts', [ProductPriceAlertController::class, 'store']);
    Route::get('/product-price-alerts/{id}', [ProductPriceAlertController::class, 'show']);
    Route::put('/product-price-alerts/{id}', [ProductPriceAlertController::class, 'update']);
    Route::delete('/product-price-alerts/{id}', [ProductPriceAlertController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Watch Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/watches', [WatchController::class, 'index']);
    Route::post('/watches', [WatchController::class, 'store']);
    Route::get('/watches/{id}', [WatchController::class, 'show']);
    Route::put('/watches/{id}', [WatchController::class, 'update']);
    Route::delete('/watches/{id}', [WatchController::class, 'destroy']);
    Route::get('/watches/{id}/observations', [ObservationController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Purchase Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('purchase-intents', PurchaseIntentController::class);
    Route::post('/purchase-intents/{id}/submit', [PurchaseIntentController::class, 'submit']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit']);
    Route::post('/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
});
