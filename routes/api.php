<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\FoodCategoryController;
use App\Http\Controllers\FoodTableController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    Route::apiResource('food-categories', FoodCategoryController::class);
    Route::apiResource('food-tables', FoodTableController::class);
});
