<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodCategory\StoreFoodCategoryRequest;
use App\Http\Requests\FoodCategory\UpdateFoodCategoryRequest;
use App\Models\FoodCategory;
use Illuminate\Http\JsonResponse;

class FoodCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(FoodCategory::all());
    }

    public function store(StoreFoodCategoryRequest $request): JsonResponse
    {
        $foodCategory = FoodCategory::query()->create($request->validated());

        return response()->json($foodCategory, 201);
    }

    public function show(FoodCategory $foodCategory): JsonResponse
    {
        return response()->json($foodCategory);
    }

    public function update(UpdateFoodCategoryRequest $request, FoodCategory $foodCategory): JsonResponse
    {
        $foodCategory->update($request->validated());

        return response()->json($foodCategory);
    }

    public function destroy(FoodCategory $foodCategory): JsonResponse
    {
        $foodCategory->delete();

        return response()->json(null, 204);
    }
}
