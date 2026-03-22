<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\FoodCategoryController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FoodTableController;
use App\Http\Controllers\FoodUnitController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\RecipeCategoryController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeItemController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    Route::apiResource('food-categories', FoodCategoryController::class);
    Route::apiResource('food-tables', FoodTableController::class);
    Route::get('foods/search', [FoodController::class, 'search'])->name('foods.search');
    Route::apiResource('foods', FoodController::class);
    Route::apiResource('foods.units', FoodUnitController::class)->shallow();

    Route::apiResource('recipe-categories', RecipeCategoryController::class);
    Route::apiResource('recipes', RecipeController::class);
    Route::apiResource('recipes.items', RecipeItemController::class)->shallow();

    Route::apiResource('meal-plans', MealPlanController::class);
});
