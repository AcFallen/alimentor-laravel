<?php

namespace App\Http\Controllers;

use App\Http\Requests\Food\StoreFoodRequest;
use App\Http\Requests\Food\UpdateFoodRequest;
use App\Models\Food;
use Illuminate\Http\JsonResponse;

class FoodController extends Controller
{
    public function index(): JsonResponse
    {
        $foods = Food::query()->with(['category', 'table'])->get();

        return response()->json($foods);
    }

    public function store(StoreFoodRequest $request): JsonResponse
    {
        $food = Food::query()->create($request->validated());

        return response()->json($food, 201);
    }

    public function show(Food $food): JsonResponse
    {
        $food->load(['category', 'table']);

        return response()->json($food);
    }

    public function update(UpdateFoodRequest $request, Food $food): JsonResponse
    {
        $food->update($request->validated());

        return response()->json($food);
    }

    public function destroy(Food $food): JsonResponse
    {
        $food->delete();

        return response()->json(null, 204);
    }
}
